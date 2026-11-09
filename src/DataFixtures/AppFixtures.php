<?php

namespace App\DataFixtures;

use App\Entity\Achat;
use App\Entity\Compte;
use App\Entity\DoseIngredient;
use App\Entity\DosePreparation;
use App\Entity\Fournisseur;
use App\Entity\GroupeChoix;
use App\Entity\Ingredient;
use App\Entity\LigneAchat;
use App\Entity\Menu;
use App\Entity\MouvementStock;
use App\Entity\OptionMenu;
use App\Entity\Plat;
use App\Entity\Preparation;
use App\Entity\Recette;
use App\Entity\TableSalle;
use App\Service\Restaurant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private Restaurant $restaurant,
        private Connection $db,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (['gerant' => 'ROLE_GERANT', 'serveur' => 'ROLE_SERVEUR', 'cuisine' => 'ROLE_CUISINE'] as $identifiant => $role) {
            if (!$manager->find(Compte::class, $identifiant)) {
                $compte = new Compte($identifiant, $role, '');
                $motDePasse = ($_SERVER['DEMO_PASSWORD'] ?? $_ENV['DEMO_PASSWORD'] ?? '') ?: 'RestaurantDemo!2026';
                $manager->persist(new Compte($identifiant, $role, $this->hasher->hashPassword($compte, $motDePasse)));
            }
        }

        // Ce menu identifie le jeu complet lors d’un nouveau chargement avec --append.
        if ($manager->getRepository(Menu::class)->findOneBy(['nom' => 'Menu du marché'])) {
            $manager->flush();

            return;
        }

        $fournisseurs = [];
        foreach (['Marché des producteurs', 'Épicerie Atlas', 'Poissonnerie du Port'] as $nom) {
            $fournisseur = $manager->getRepository(Fournisseur::class)->findOneBy(['nom' => $nom]) ?? (new Fournisseur())->setNom($nom);
            $manager->persist($fournisseur);
            $fournisseurs[] = $fournisseur;
        }

        $date = new \DateTimeImmutable('2026-11-09 08:00:00', new \DateTimeZone('Africa/Casablanca'));
        $achats = [];
        foreach ($fournisseurs as $fournisseur) {
            $achat = (new Achat())->setFournisseur($fournisseur)->setDateAchat($date)->setStatut('recu');
            $manager->persist($achat);
            $achats[] = $achat;
        }

        $ingredients = [];
        $achatsDemo = [
            ['Tomate', 'Oignon', 'Ail', 'Carotte', 'Courgette', 'Aubergine', 'Poivron', 'Pomme de terre', 'Citron',
                'Concombre', 'Laitue', 'Persil', 'Coriandre', 'Menthe', 'Basilic', 'Champignon', 'Pomme', 'Orange',
                'Fraise', 'Poire', 'Poulet', 'Bœuf', 'Agneau', 'Œuf'],
            ['Farine', 'Semoule', 'Riz', 'Pâtes', 'Lentille', 'Pois chiche', 'Sucre', 'Sel', 'Poivre', 'Cumin',
                'Paprika', 'Cannelle', 'Levure', 'Chocolat noir', 'Amande', 'Mozzarella', 'Parmesan', 'Beurre',
                'Huile d’olive', 'Lait', 'Crème', 'Eau de fleur d’oranger'],
            ['Sardine', 'Crevette', 'Cabillaud', 'Calamar'],
        ];
        foreach ($achatsDemo as $index => $noms) {
            foreach ($noms as $nom) {
                $unite = match ($nom) {
                    'Œuf' => 'piece',
                    'Huile d’olive', 'Lait', 'Crème', 'Eau de fleur d’oranger' => 'ml',
                    default => 'g',
                };
                $ingredient = $manager->getRepository(Ingredient::class)->findOneBy(['nom' => $nom])
                    ?? (new Ingredient())->setNom($nom)->setUniteStock($unite);
                $manager->persist($ingredient);
                $ingredients[$nom] = $ingredient;
                $prix = match ($nom) {
                    'Tomate' => '8.00', 'Farine' => '12.00', 'Mozzarella' => '85.00', 'Lait' => '10.00',
                    'Œuf' => '1.80', 'Huile d’olive' => '75.00', 'Agneau' => '115.00', 'Bœuf' => '95.00',
                    'Poulet' => '38.00', 'Crevette' => '115.00', 'Cabillaud' => '105.00',
                    default => ['18.00', '24.00', '55.00'][$index],
                };
                $quantite = $unite === 'piece' ? '80' : ($unite === 'ml' ? '4' : '6');
                $ligne = (new LigneAchat())->setAchat($achats[$index])->setIngredient($ingredient)
                    ->setQuantite($quantite)->setUniteAchat(match ($unite) {'g' => 'kg', 'ml' => 'l', default => 'piece'})
                    ->setPrixUnitaire($prix);
                $manager->persist($ligne);
                $manager->persist((new MouvementStock())->setIngredient($ingredient)->setLigneAchat($ligne)
                    ->setDateMouvement($date)->setType('entree')->setQuantite((string) ((int) $quantite * ($unite === 'piece' ? 1 : 1000)))
                    ->setMotif('Réception du stock de démonstration'));
            }
        }

        $sauce = $manager->getRepository(Preparation::class)->findOneBy(['nom' => 'Sauce tomate']);
        if (!$sauce) {
            $sauce = (new Preparation())->setNom('Sauce tomate');
            $recette = (new Recette())->setPreparation($sauce)->setRendement('1000')->setUniteRendement('g');
            $manager->persist($sauce);
            $manager->persist($recette);
            $this->doser($manager, $recette, $ingredients, ['Tomate' => 1200, 'Oignon' => 120, 'Ail' => 15, 'Huile d’olive' => 40, 'Basilic' => 10, 'Sel' => 8]);
        }

        $carte = [
            'Pizza margherita' => [60, ['Farine' => 250, 'Mozzarella' => 120, 'Levure' => 3, 'Basilic' => 4], 80],
            'Pizza champignons' => [72, ['Farine' => 250, 'Champignon' => 100, 'Mozzarella' => 95, 'Levure' => 3], 80],
            'Pâtes à la tomate' => [55, ['Pâtes' => 140, 'Parmesan' => 18, 'Basilic' => 4], 120],
            'Gratin d’aubergines' => [67, ['Aubergine' => 230, 'Mozzarella' => 60, 'Parmesan' => 15], 110],
            'Salade du marché' => [38, ['Laitue' => 80, 'Concombre' => 60, 'Tomate' => 80, 'Citron' => 20, 'Huile d’olive' => 12]],
            'Salade de pois chiches' => [41, ['Pois chiche' => 90, 'Poivron' => 50, 'Coriandre' => 10, 'Citron' => 20, 'Cumin' => 2]],
            'Soupe de lentilles' => [42, ['Lentille' => 75, 'Carotte' => 50, 'Oignon' => 30, 'Cumin' => 2, 'Persil' => 5]],
            'Velouté de courgette' => [39, ['Courgette' => 170, 'Pomme de terre' => 55, 'Crème' => 35, 'Poivre' => 2]],
            'Couscous au poulet' => [89, ['Semoule' => 145, 'Poulet' => 160, 'Carotte' => 50, 'Courgette' => 50, 'Pois chiche' => 35]],
            'Tajine de bœuf' => [105, ['Bœuf' => 180, 'Oignon' => 65, 'Pomme de terre' => 130, 'Paprika' => 3]],
            'Brochettes d’agneau' => [118, ['Agneau' => 190, 'Poivron' => 55, 'Oignon' => 45, 'Cumin' => 3]],
            'Riz aux crevettes' => [96, ['Riz' => 120, 'Crevette' => 150, 'Ail' => 6, 'Persil' => 8, 'Huile d’olive' => 15]],
            'Sardines grillées' => [64, ['Sardine' => 230, 'Citron' => 35, 'Coriandre' => 8, 'Paprika' => 3]],
            'Cabillaud au citron' => [105, ['Cabillaud' => 170, 'Citron' => 40, 'Beurre' => 25, 'Pomme de terre' => 150]],
            'Calamars à la tomate' => [87, ['Calamar' => 170, 'Ail' => 8, 'Persil' => 10], 110],
            'Omelette aux herbes' => [43, ['Œuf' => 3, 'Persil' => 8, 'Coriandre' => 8, 'Beurre' => 15]],
            'Mousse au chocolat' => [39, ['Chocolat noir' => 55, 'Œuf' => 1, 'Sucre' => 15, 'Crème' => 30]],
            'Salade de fruits' => [35, ['Pomme' => 55, 'Orange' => 70, 'Fraise' => 55, 'Poire' => 50, 'Menthe' => 4]],
            'Flan à la fleur d’oranger' => [34, ['Lait' => 150, 'Œuf' => 1, 'Sucre' => 20, 'Eau de fleur d’oranger' => 6]],
            'Tarte amande et poire' => [46, ['Amande' => 45, 'Poire' => 75, 'Farine' => 50, 'Beurre' => 25, 'Cannelle' => 2, 'Sucre' => 18]],
        ];

        $plats = [];
        foreach ($carte as $nom => [$prix, $doses]) {
            $plat = $manager->getRepository(Plat::class)->findOneBy(['nom' => $nom]) ?? (new Plat())->setNom($nom)->setPrixVente((string) $prix);
            $plats[$nom] = $plat;
            $manager->persist($plat);
            if ($plat->getId() !== null && $manager->getRepository(Recette::class)->findOneBy(['plat' => $plat])) {
                continue;
            }
            $recette = (new Recette())->setPlat($plat)->setRendement('1')->setUniteRendement('portion');
            $manager->persist($recette);
            $this->doser($manager, $recette, $ingredients, $doses);
            if (isset($carte[$nom][2])) {
                $manager->persist((new DosePreparation())->setRecette($recette)->setPreparation($sauce)
                    ->setUnite('g')->setQuantite((string) $carte[$nom][2]));
            }
        }

        $groupes = [];
        foreach ([
            'Menu du marché' => [109, ['Entrée' => ['Salade du marché', 'Soupe de lentilles'], 'Plat' => ['Pizza margherita', 'Pâtes à la tomate'], 'Dessert' => ['Salade de fruits', 'Mousse au chocolat']]],
            'Menu de la mer' => [139, ['Entrée' => ['Velouté de courgette'], 'Plat' => ['Sardines grillées', 'Riz aux crevettes'], 'Dessert' => ['Flan à la fleur d’oranger', 'Salade de fruits']]],
        ] as $nom => [$prix, $options]) {
            $menu = (new Menu())->setNom($nom)->setPrixVente((string) $prix);
            $manager->persist($menu);
            foreach ($options as $libelle => $choix) {
                $groupe = (new GroupeChoix())->setMenu($menu)->setLibelle($libelle);
                $manager->persist($groupe);
                $groupes[$nom][$libelle] = [$groupe, $plats[$choix[0]]];
                foreach ($choix as $plat) {
                    $manager->persist((new OptionMenu())->setGroupeChoix($groupe)->setPlat($plats[$plat]));
                }
            }
            $menus[$nom] = $menu;
        }

        $tables = [];
        for ($numero = 1; $numero <= 15; ++$numero) {
            $nom = sprintf('T%02d', $numero);
            $table = $manager->getRepository(TableSalle::class)->findOneBy(['numero' => $nom])
                ?? (new TableSalle())->setNumero($nom)->setCapacite(4);
            $manager->persist($table);
            $tables[] = $table;
        }
        $manager->flush();

        $choix = [];
        foreach ($groupes['Menu du marché'] as [$groupe, $plat]) {
            $choix[$groupe->getId()] = $plat->getId();
        }
        $terminee = $this->restaurant->commander($tables[0]->getId(), [
            ['article' => 'menu:'.$menus['Menu du marché']->getId(), 'quantite' => 2, 'choix' => $choix],
            ['article' => 'plat:'.$plats['Pizza champignons']->getId(), 'quantite' => 1],
        ]);
        $ids = $this->db->fetchFirstColumn(
            'SELECT s.id FROM plat_service s JOIN ligne_commande l ON s.ligne_commande_id = l.id WHERE l.commande_id = ?',
            [$terminee],
        );
        foreach ($ids as $id) {
            foreach (['en_cours', 'pret', 'servi'] as $statut) {
                $this->restaurant->changerStatut((int) $id, $statut);
            }
        }
        $this->restaurant->commander($tables[1]->getId(), [['article' => 'plat:'.$plats['Couscous au poulet']->getId(), 'quantite' => 2]]);
    }

    private function doser(ObjectManager $manager, Recette $recette, array $ingredients, array $doses): void
    {
        foreach ($doses as $nom => $quantite) {
            $ingredient = $ingredients[$nom];
            $manager->persist((new DoseIngredient())->setRecette($recette)->setIngredient($ingredient)
                ->setQuantite((string) $quantite)->setUnite($ingredient->getUniteStock()));
        }
    }
}
