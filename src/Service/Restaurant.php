<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

final class Restaurant
{
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
