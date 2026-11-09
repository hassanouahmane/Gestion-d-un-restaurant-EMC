<?php

declare(strict_types=1);

use App\Kernel;
use App\Service\Allergenes;
use App\Service\Restaurant;
use App\Service\Unites;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], false);
$kernel->boot();
$db = $kernel->getContainer()->get('doctrine')->getConnection();
$restaurant = new Restaurant($db);
$prefixe = 'Test '.bin2hex(random_bytes(4));

function verifier(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function doitRefuser(callable $action, string $message): void
{
    try {
        $action();
    } catch (DomainException) {
        return;
    }

    throw new RuntimeException($message);
}

verifier(Unites::convertir(1.25, 'kg', 'g') === 1250.0, 'Conversion kg vers g.');
verifier(Unites::convertir(1.5, 'litre', 'ml') === 1500.0, 'Conversion litres vers ml.');
doitRefuser(fn () => Unites::convertir(1, 'piece', 'kg'), 'Une pièce ne se convertit pas en masse.');

$db->beginTransaction();
try {
    $ajouter = static function (string $table, array $donnees) use ($db): int {
        $db->insert($table, $donnees);

        return (int) $db->lastInsertId();
    };
    $ingredient = $ajouter('ingredient', ['nom' => "$prefixe tomate", 'unite_stock' => 'g']);
    $plat = $ajouter('plat', ['nom' => "$prefixe plat", 'prix_vente' => '10.00']);
    $preparation = $ajouter('preparation', ['nom' => "$prefixe sauce"]);
    $recette = $ajouter('recette', ['plat_id' => $plat, 'rendement' => 2, 'unite_rendement' => 'portion']);
    $recetteSauce = $ajouter('recette', ['preparation_id' => $preparation, 'rendement' => 1, 'unite_rendement' => 'kg']);
    $ajouter('dose_ingredient', ['recette_id' => $recette, 'ingredient_id' => $ingredient, 'quantite' => 200, 'unite' => 'g']);
    $ajouter('dose_ingredient', ['recette_id' => $recetteSauce, 'ingredient_id' => $ingredient, 'quantite' => 2, 'unite' => 'kg']);
    $ajouter('dose_preparation', ['recette_id' => $recette, 'preparation_id' => $preparation, 'quantite' => 50, 'unite' => 'g']);
    $table = $ajouter('table_salle', ['numero' => $prefixe, 'capacite' => 4]);
    $fournisseur = $ajouter('fournisseur', ['nom' => $prefixe]);
    $achat = $ajouter('achat', ['fournisseur_id' => $fournisseur, 'date_achat' => '2026-01-01', 'statut' => 'brouillon']);
    $produit = $ajouter('produit_achete', ['ingredient_id' => $ingredient, 'nom' => $prefixe, 'code_barres' => (string) random_int(1000000000000, 9999999999999), 'allergenes' => '["en:milk"]', 'etat' => 'confirme']);
    $ligne = $ajouter('ligne_achat', ['achat_id' => $achat, 'ingredient_id' => $ingredient, 'quantite' => 1, 'unite_achat' => 'kg', 'prix_unitaire' => 20, 'produit_achete_id' => $produit]);
    $stock = fn (): float => (float) $restaurant->stocks()[$ingredient]['stock'];

    verifier(abs($restaurant->besoins($plat)[$ingredient] - 200.0) < 0.00001, 'Dose directe et sauce ramenées à une portion.');
    verifier(!$restaurant->carte()[$plat]['disponible'], 'Sans stock, le plat est indisponible.');
    $restaurant->recevoir($achat);
    verifier($stock() === 1000.0, 'La réception ajoute un kilogramme au stock en grammes.');
    doitRefuser(fn () => $restaurant->recevoir($achat), 'Une réception répétée doit être bloquée.');

    $menu = $ajouter('menu', ['nom' => $prefixe, 'prix_vente' => '15.00']);
    $groupe = $ajouter('groupe_choix', ['menu_id' => $menu, 'libelle' => 'Plat']);
    $ajouter('option_menu', ['groupe_choix_id' => $groupe, 'plat_id' => $plat]);
    doitRefuser(fn () => $restaurant->commander($table, [['article' => "menu:$menu", 'quantite' => 1, 'choix' => [$groupe => 0]]]), 'Un choix absent des options doit être refusé.');
    verifier((int) $db->fetchOne('SELECT COUNT(*) FROM commande WHERE table_salle_id = ?', [$table]) === 0, 'Une commande invalide ne laisse aucun brouillon.');

    $commande = $restaurant->commander($table, [['article' => "menu:$menu", 'quantite' => 5, 'choix' => [$groupe => $plat]]]);
    verifier($stock() === 1000.0 && !$restaurant->carte()[$plat]['disponible'], 'La réservation bloque la vente tout en conservant le stock physique.');
    doitRefuser(fn () => $restaurant->commander($table, [['article' => "plat:$plat", 'quantite' => 1]]), 'Un stock réservé ne peut être vendu deux fois.');
    $service = (int) $db->fetchOne('SELECT s.id FROM plat_service s JOIN ligne_commande l ON s.ligne_commande_id = l.id WHERE l.commande_id = ?', [$commande]);
    doitRefuser(fn () => $restaurant->changerStatut($service, 'servi'), 'Un plat doit passer par la préparation.');
    foreach (['en_cours', 'pret', 'servi'] as $statut) {
        $restaurant->changerStatut($service, $statut);
    }
    verifier($stock() === 0.0, 'Le service consomme la totalité du stock réservé.');
    doitRefuser(fn () => $restaurant->changerStatut($service, 'servi'), 'Un plat ne peut pas être servi deux fois.');
    $db->update('ligne_achat', ['prix_unitaire' => 50], ['id' => $ligne]);
    verifier((float) $db->fetchOne('SELECT cout_unitaire FROM plat_service WHERE id = ?', [$service]) === 4.0, 'Le coût historique est figé.');

    $restaurant->compter($ingredient, 350.0);
    verifier($stock() === 350.0, 'Le comptage ajuste le stock.');
    verifier((float) $db->fetchOne('SELECT stock_compte - stock_theorique FROM inventaire WHERE ingredient_id = ?', [$ingredient]) === 350.0, 'L’écart est enregistré.');
    doitRefuser(fn () => $restaurant->perte($ingredient, 400.0, 'Casse'), 'Une perte ne rend jamais le stock négatif.');
    $restaurant->perte($ingredient, 10.0, 'Casse');
    verifier($stock() === 340.0, 'La perte est un mouvement de sortie.');

    $allergenes = new Allergenes($db, $restaurant);
    verifier($allergenes->pourPlat($plat) === ['tags' => ['en:milk'], 'incomplet' => false], 'Les allergènes confirmés du produit acheté remontent au plat.');
    $allergenes->valider($produit, 'a_verifier');
    verifier($allergenes->pourPlat($plat)['incomplet'], 'Une fiche incohérente rend les informations incomplètes.');

    echo "Règles métier vérifiées sur MySQL.\n";
} finally {
    $db->rollBack();
}
