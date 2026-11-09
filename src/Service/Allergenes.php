<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

final class Allergenes
{
    public function __construct(private Connection $db, private Restaurant $restaurant)
    {
    }

    public function rechercher(string $code): array
    {
        if (!preg_match('/^[0-9]{8,14}$/D', $code)) {
            throw new \DomainException('Entre un code-barres de 8 à 14 chiffres.');
        }

        $context = stream_context_create(['http' => [
            'timeout' => 5,
            'follow_location' => 0,
            'header' => "User-Agent: Restaurant-PFE-EMC/1.0\r\nAccept: application/json\r\n",
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents(
            "https://world.openfoodfacts.org/api/v2/product/$code.json?fields=product_name,allergens_tags,ingredients_text",
            false,
            $context,
        );
        $data = is_string($response) ? json_decode($response, true) : null;
        if (!is_array($data) || !isset($data['status'])) {
            throw new \DomainException('Open Food Facts est temporairement indisponible.');
        }
        if ((int) $data['status'] !== 1 || !is_array($data['product'] ?? null)) {
            throw new \DomainException('Ce code-barres est absent d’Open Food Facts.');
        }

        $produit = $data['product'];
        $tags = $produit['allergens_tags'] ?? null;
        $allergenes = null;
        if (is_array($tags) && $tags !== [] && array_filter($tags, 'is_string') === $tags) {
            $allergenes = array_values(array_unique($tags));
        }

        return ['nom' => mb_substr(($produit['product_name'] ?? '') ?: "Produit $code", 0, 150), 'allergenes' => $allergenes];
    }

    public function rattacher(int $ingredient, string $code): void
    {
        if (!$this->db->fetchOne('SELECT 1 FROM ingredient WHERE id = ?', [$ingredient])) {
            throw new \DomainException('Ingrédient inconnu.');
        }
        $existant = $this->db->fetchOne('SELECT ingredient_id FROM produit_achete WHERE code_barres = ?', [$code]);
        if ($existant !== false && (int) $existant !== $ingredient) {
            throw new \DomainException('Ce code-barres est déjà rattaché à un autre ingrédient.');
        }
        $produit = $this->rechercher($code);
        $this->db->executeStatement(
            'INSERT INTO produit_achete (ingredient_id, nom, code_barres, allergenes, etat)
             VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nom = ?, allergenes = ?, etat = ?',
            [
                $ingredient, $produit['nom'], $code,
                $produit['allergenes'] === null ? null : json_encode($produit['allergenes'], JSON_THROW_ON_ERROR), 'a_verifier',
                $produit['nom'], $produit['allergenes'] === null ? null : json_encode($produit['allergenes'], JSON_THROW_ON_ERROR), 'a_verifier',
            ],
        );
    }

    public function valider(int $id, string $decision): void
    {
        $produit = $this->db->fetchAssociative('SELECT allergenes, etat FROM produit_achete WHERE id = ?', [$id]);
        if (!$produit || !in_array($decision, ['confirme', 'a_verifier'], true)
            || ($decision === 'confirme' && $produit['allergenes'] === null)) {
            throw new \DomainException('Une information absente ne peut pas être confirmée.');
        }
        $this->db->update('produit_achete', ['etat' => $decision], ['id' => $id]);
    }

    public function pourPlat(int $plat): array
    {
        try {
            $ingredients = array_keys($this->restaurant->besoins($plat));
        } catch (\DomainException) {
            return ['tags' => [], 'incomplet' => true];
        }

        $tags = [];
        $incomplet = false;
        foreach ($ingredients as $id) {
            $produits = $this->db->fetchAllAssociative(
                'SELECT p.etat, p.allergenes FROM produit_achete p WHERE p.ingredient_id = ?
                 AND EXISTS (SELECT 1 FROM ligne_achat l JOIN achat a ON l.achat_id = a.id
                             WHERE l.produit_achete_id = p.id AND a.statut = ?)',
                [$id, 'recu'],
            );
            if ($produits === [] || $this->db->fetchOne(
                "SELECT 1 FROM ligne_achat l JOIN achat a ON a.id = l.achat_id
                 WHERE l.ingredient_id = ? AND a.statut = 'recu' AND l.produit_achete_id IS NULL LIMIT 1", [$id],
            )) {
                $incomplet = true;
            }
            foreach ($produits as $produit) {
                if ($produit['etat'] !== 'confirme') {
                    $incomplet = true;
                    continue;
                }
                $tags = array_merge($tags, json_decode($produit['allergenes'], true, 512, JSON_THROW_ON_ERROR));
            }
        }

        return ['tags' => array_values(array_unique($tags)), 'incomplet' => $incomplet];
    }
}
