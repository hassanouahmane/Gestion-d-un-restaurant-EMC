<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

final class Restaurant
{
    private const TRANSITIONS = [
        'a_preparer' => ['en_cours', 'annule'],
        'en_cours' => ['pret', 'annule'],
        'pret' => ['servi', 'annule'],
    ];

    public function __construct(private Connection $db)
    {
    }

    public function stocks(): array
    {
        return $this->db->fetchAllAssociativeIndexed(<<<'SQL'
            SELECT i.id, i.nom, i.unite_stock,
                   COALESCE(SUM(CASE WHEN m.type = 'entree' THEN m.quantite ELSE -m.quantite END), 0) AS stock
            FROM ingredient i LEFT JOIN mouvement_stock m ON m.ingredient_id = i.id
            GROUP BY i.id, i.nom, i.unite_stock
            ORDER BY i.nom
            SQL);
    }

    public function besoins(int $plat): array
    {
        $recette = $this->db->fetchAssociative('SELECT id, rendement, unite_rendement FROM recette WHERE plat_id = ?', [$plat]);
        if (!$recette) {
            throw new \DomainException('Ce plat doit avoir une recette.');
        }

        $rendement = Unites::convertir((float) $recette['rendement'], $recette['unite_rendement'], 'portion');
        $besoins = $this->dosesIngredients((int) $recette['id'], 1 / $rendement);
        $preparations = $this->db->fetchAllAssociative(
            'SELECT d.quantite, d.unite, r.id AS recette_id, r.rendement, r.unite_rendement
             FROM dose_preparation d LEFT JOIN recette r ON r.preparation_id = d.preparation_id WHERE d.recette_id = ?',
            [$recette['id']],
        );
        foreach ($preparations as $dose) {
            if ($dose['recette_id'] === null) {
                throw new \DomainException('Une préparation est sans recette.');
            }
            $quantite = Unites::convertir((float) $dose['quantite'], $dose['unite'], $dose['unite_rendement']);
            $ingredients = $this->dosesIngredients((int) $dose['recette_id'], $quantite / (float) $dose['rendement']);
            if ($ingredients === [] || $this->db->fetchOne('SELECT 1 FROM dose_preparation WHERE recette_id = ? LIMIT 1', [$dose['recette_id']])) {
                throw new \DomainException('Une préparation doit contenir uniquement des ingrédients.');
            }
            foreach ($ingredients as $id => $besoin) {
                $besoins[$id] = ($besoins[$id] ?? 0) + $besoin;
            }
        }
        if ($besoins === []) {
            throw new \DomainException('La recette de ce plat est vide.');
        }

        return $besoins;
    }

    public function carte(): array
    {
        $stocks = $this->stocks();
        $reserves = $this->reserves();
        $plats = $this->db->fetchAllAssociativeIndexed('SELECT id, nom, prix_vente FROM plat ORDER BY nom');
        foreach ($plats as $id => &$plat) {
            try {
                $besoins = $this->besoins((int) $id);
                $this->verifierStock($this->quantifier($besoins, 1), $stocks, $reserves);
                $plat['disponible'] = true;
            } catch (\DomainException) {
                $plat['disponible'] = false;
            }
        }

        return $plats;
    }

    public function menus(): array
    {
        $menus = $this->db->fetchAllAssociativeIndexed('SELECT id, nom, prix_vente FROM menu ORDER BY nom');
        foreach ($menus as $id => &$menu) {
            $menu['groupes'] = $this->db->fetchAllAssociativeIndexed(
                'SELECT id, libelle FROM groupe_choix WHERE menu_id = ? ORDER BY id', [$id],
            );
            foreach ($menu['groupes'] as $groupeId => &$groupe) {
                $groupe['plats'] = $this->db->fetchFirstColumn('SELECT plat_id FROM option_menu WHERE groupe_choix_id = ? ORDER BY plat_id', [$groupeId]);
            }
            unset($groupe);
        }

        return $menus;
    }

    public function commander(int $table, array $articles): int
    {
        if (count($articles) < 1 || count($articles) > 30) {
            throw new \DomainException('Une commande doit contenir entre 1 et 30 lignes.');
        }

        return $this->db->transactional(function () use ($table, $articles): int {
            $this->verrouillerStock();
            $stocks = $this->stocks();
            $reserves = $this->reserves();
            $menus = $this->menus();
            if (!$this->db->fetchOne('SELECT 1 FROM table_salle WHERE id = ?', [$table])) {
                throw new \DomainException('Table inconnue.');
            }
            $this->db->insert('commande', ['table_salle_id' => $table, 'date_commande' => $this->maintenant(), 'statut' => 'envoyee']);
            $commande = (int) $this->db->lastInsertId();

            foreach ($articles as $article) {
                $quantite = filter_var($article['quantite'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 60]]);
                if ($quantite === false || !preg_match('/^(plat|menu):([1-9][0-9]*)$/D', (string) ($article['article'] ?? ''), $match)) {
                    throw new \DomainException('Article ou quantité invalide.');
                }
                $id = (int) $match[2];
                $estMenu = $match[1] === 'menu';
                $choix = [];
                if ($estMenu) {
                    $menu = $menus[$id] ?? null;
                    if (!$menu || $menu['groupes'] === []) {
                        throw new \DomainException('Ce menu ne contient aucun groupe de choix.');
                    }
                    $prix = (string) $menu['prix_vente'];
                    foreach ($menu['groupes'] as $groupeId => $groupe) {
                        $plat = filter_var($article['choix'][$groupeId] ?? null, FILTER_VALIDATE_INT);
                        if ($plat === false || !in_array($plat, array_map('intval', $groupe['plats']), true)) {
                            throw new \DomainException('Sélectionne un plat autorisé dans chaque groupe du menu.');
                        }
                        $choix[$groupeId] = $plat;
                    }
                } else {
                    $prix = $this->db->fetchOne('SELECT prix_vente FROM plat WHERE id = ?', [$id]);
                    if ($prix === false) {
                        throw new \DomainException('Plat inconnu.');
                    }
                }

                $this->db->insert('ligne_commande', [
                    'commande_id' => $commande, 'plat_id' => $estMenu ? null : $id, 'menu_id' => $estMenu ? $id : null,
                    'quantite' => $quantite, 'prix_unitaire_applique' => $prix,
                ]);
                $ligne = (int) $this->db->lastInsertId();
                $plats = $estMenu ? $choix : [$id];
                $prixRestant = (int) round((float) $prix * 100);
                $platsRestants = count($plats);
                foreach ($plats as $groupeId => $plat) {
                    if ($estMenu) {
                        $this->db->insert('plat_choisi', ['ligne_commande_id' => $ligne, 'groupe_choix_id' => $groupeId, 'plat_id' => $plat]);
                    }
                    $besoins = $this->quantifier($this->besoins($plat), $quantite);
                    $this->verifierStock($besoins, $stocks, $reserves);
                    foreach ($besoins as $ingredient => $besoin) {
                        $reserves[$ingredient] = ($reserves[$ingredient] ?? 0) + $besoin;
                    }
                    $partPrix = intdiv($prixRestant, $platsRestants--);
                    $prixRestant -= $partPrix;
                    $this->db->insert('plat_service', [
                        'ligne_commande_id' => $ligne, 'plat_id' => $plat, 'quantite' => $quantite,
                        'statut' => 'a_preparer', 'besoins' => json_encode($besoins, JSON_THROW_ON_ERROR),
                        'prix_vente_unitaire' => Unites::decimal($partPrix / 100, 2),
                    ]);
                }
            }

            return $commande;
        });
    }

    public function changerStatut(int $id, string $statut): void
    {
        $this->db->transactional(function () use ($id, $statut): void {
            $this->verrouillerStock();
            $plat = $this->db->fetchAssociative('SELECT * FROM plat_service WHERE id = ? FOR UPDATE', [$id]);
            if (!$plat || !in_array($statut, self::TRANSITIONS[$plat['statut']] ?? [], true)) {
                throw new \DomainException('Ce changement de statut est impossible.');
            }

            $modifications = ['statut' => $statut];
            if ($statut === 'servi') {
                $besoins = json_decode($plat['besoins'], true, 512, JSON_THROW_ON_ERROR);
                $stocks = $this->stocks();
                $this->verifierStock($besoins, $stocks);
                $cout = 0.0;
                $coutConnu = true;
                foreach ($besoins as $ingredient => $quantite) {
                    $achat = $this->db->fetchAssociative(
                        "SELECT l.prix_unitaire, l.unite_achat FROM ligne_achat l JOIN achat a ON a.id = l.achat_id
                         WHERE l.ingredient_id = ? AND a.statut = 'recu' ORDER BY a.date_achat DESC, l.id DESC LIMIT 1",
                        [$ingredient],
                    );
                    if ($achat) {
                        $cout += (float) $achat['prix_unitaire'] * Unites::convertir($quantite, $stocks[$ingredient]['unite_stock'], $achat['unite_achat']);
                    } else {
                        $coutConnu = false;
                    }
                    $this->mouvement($ingredient, 'sortie', $quantite, "Service du plat #$id");
                }
                $modifications['cout_unitaire'] = $coutConnu ? Unites::decimal($cout / (int) $plat['quantite'], 2) : null;
            }
            $this->db->update('plat_service', $modifications, ['id' => $id]);
            $commande = $this->db->fetchOne('SELECT commande_id FROM ligne_commande WHERE id = ?', [$plat['ligne_commande_id']]);
            $restants = $this->db->fetchOne(
                "SELECT COUNT(*) FROM plat_service s JOIN ligne_commande l ON s.ligne_commande_id = l.id
                 WHERE l.commande_id = ? AND s.statut NOT IN ('servi', 'annule')", [$commande],
            );
            if ((int) $restants === 0) {
                $this->db->update('commande', ['statut' => 'terminee'], ['id' => $commande]);
            }
        });
    }

    public function service(): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT s.id, s.statut, s.quantite, p.nom, t.numero, c.id AS commande
             FROM plat_service s JOIN plat p ON s.plat_id = p.id JOIN ligne_commande l ON s.ligne_commande_id = l.id
             JOIN commande c ON l.commande_id = c.id JOIN table_salle t ON t.id = c.table_salle_id
             ORDER BY c.id DESC, s.id LIMIT 100',
        );
    }

    public function recevoir(int $achat): void
    {
        $this->db->transactional(function () use ($achat): void {
            $this->verrouillerStock();
            $statut = $this->db->fetchOne('SELECT statut FROM achat WHERE id = ? FOR UPDATE', [$achat]);
            $lignes = $this->db->fetchAllAssociative(
                'SELECT l.*, i.unite_stock FROM ligne_achat l JOIN ingredient i ON i.id = l.ingredient_id WHERE l.achat_id = ?',
                [$achat],
            );
            if ($statut !== 'brouillon' || $lignes === []) {
                throw new \DomainException('Seul un achat en brouillon avec des lignes peut être réceptionné.');
            }
            foreach ($lignes as $ligne) {
                $quantite = Unites::convertir((float) $ligne['quantite'], $ligne['unite_achat'], $ligne['unite_stock']);
                $this->mouvement((int) $ligne['ingredient_id'], 'entree', $quantite, "Réception de l’achat #$achat", (int) $ligne['id']);
            }
            $this->db->update('achat', ['statut' => 'recu'], ['id' => $achat]);
        });
    }

    public function compter(int $ingredient, float $compte): void
    {
        $this->db->transactional(function () use ($ingredient, $compte): void {
            $this->verrouillerStock();
            $stock = $this->stocks()[$ingredient] ?? null;
            if ($stock === null || $compte < 0 || $compte > 9999999999.9999 || !is_finite($compte)) {
                throw new \DomainException('Quantité comptée ou ingrédient invalide.');
            }
            $this->db->insert('inventaire', [
                'ingredient_id' => $ingredient, 'date_comptage' => $this->maintenant(),
                'stock_theorique' => $stock['stock'], 'stock_compte' => Unites::decimal($compte),
            ]);
            $id = $this->db->lastInsertId();
            $ecart = round($compte - (float) $stock['stock'], 4);
            if ($ecart !== 0.0) {
                $this->mouvement($ingredient, $ecart > 0 ? 'entree' : 'sortie', abs($ecart), "Inventaire #$id");
            }
        });
    }

    public function perte(int $ingredient, float $quantite, string $motif): void
    {
        $this->db->transactional(function () use ($ingredient, $quantite, $motif): void {
            $this->verrouillerStock();
            if (!in_array($motif, ['Casse', 'Péremption', 'Erreur en cuisine'], true) || $quantite <= 0 || !is_finite($quantite)) {
                throw new \DomainException('Quantité ou motif de perte invalide.');
            }
            $this->verifierStock([$ingredient => $quantite], $this->stocks());
            $this->mouvement($ingredient, 'sortie', $quantite, $motif);
        });
    }

    public function ventes(): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT p.nom, SUM(s.quantite) AS quantite, SUM(s.quantite * s.prix_vente_unitaire) AS ventes,
                    CASE WHEN COUNT(s.cout_unitaire) = COUNT(*) THEN SUM(s.quantite * (s.prix_vente_unitaire - s.cout_unitaire)) END AS marge
             FROM plat_service s JOIN plat p ON s.plat_id = p.id WHERE s.statut = 'servi'
             GROUP BY p.id, p.nom ORDER BY quantite DESC, p.nom",
        );
    }

    public function suggestions(): array
    {
        $suggestions = [];
        $stocks = $this->stocks();
        $reserves = $this->reserves();
        $objectif = [];
        foreach ($this->db->fetchFirstColumn('SELECT id FROM plat') as $plat) {
            try {
                foreach ($this->besoins((int) $plat) as $id => $quantite) {
                    $objectif[$id] = ($objectif[$id] ?? 0) + $quantite * 5;
                }
            } catch (\DomainException) {
                continue;
            }
        }
        foreach ($objectif as $id => $quantite) {
            $manque = round($quantite + ($reserves[$id] ?? 0) - (float) $stocks[$id]['stock'], 4);
            if ($manque > 0) {
                $suggestions[] = ['nom' => $stocks[$id]['nom'], 'unite' => $stocks[$id]['unite_stock'], 'quantite' => $manque];
            }
        }

        return $suggestions;
    }

    private function dosesIngredients(int $recette, float $facteur): array
    {
        $besoins = [];
        $doses = $this->db->fetchAllAssociative(
            'SELECT d.*, i.unite_stock FROM dose_ingredient d JOIN ingredient i ON d.ingredient_id = i.id WHERE d.recette_id = ?',
            [$recette],
        );
        foreach ($doses as $dose) {
            $besoins[$dose['ingredient_id']] = $facteur * Unites::convertir((float) $dose['quantite'], $dose['unite'], $dose['unite_stock']);
        }

        return $besoins;
    }

    private function quantifier(array $besoins, int $portions): array
    {
        return array_map(static fn (float $quantite): float => max(0.0001, round($quantite * $portions, 4)), $besoins);
    }

    private function reserves(): array
    {
        $reserves = [];
        foreach ($this->db->fetchFirstColumn("SELECT besoins FROM plat_service WHERE statut IN ('a_preparer', 'en_cours', 'pret')") as $json) {
            foreach (json_decode($json, true, 512, JSON_THROW_ON_ERROR) as $id => $quantite) {
                $reserves[$id] = ($reserves[$id] ?? 0) + $quantite;
            }
        }

        return $reserves;
    }

    private function verifierStock(array $besoins, array $stocks, array $reserves = []): void
    {
        foreach ($besoins as $id => $quantite) {
            $stock = $stocks[$id] ?? null;
            if ($stock === null || round((float) $stock['stock'] - ($reserves[$id] ?? 0) - $quantite, 4) < 0) {
                throw new \DomainException('Stock insuffisant pour '.($stock['nom'] ?? 'un ingrédient').'.');
            }
        }
    }

    private function verrouillerStock(): void
    {
        $this->db->fetchFirstColumn('SELECT id FROM ingredient ORDER BY id FOR UPDATE');
    }

    private function mouvement(int $ingredient, string $type, float $quantite, string $motif, ?int $ligne = null): void
    {
        if (round($quantite, 4) <= 0 || $quantite > 9999999999.9999) {
            throw new \DomainException('Quantité de mouvement hors limites.');
        }
        $this->db->insert('mouvement_stock', [
            'ingredient_id' => $ingredient, 'type' => $type, 'quantite' => Unites::decimal($quantite),
            'motif' => $motif, 'ligne_achat_id' => $ligne, 'date_mouvement' => $this->maintenant(),
        ]);
    }

    private function maintenant(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Africa/Casablanca')))->format('Y-m-d H:i:s');
    }
}
