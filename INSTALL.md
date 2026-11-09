# Installation du restaurant

## Avec Docker

Prérequis · Docker Desktop ou Docker Engine avec le plugin Compose. La première construction télécharge les images et les dépendances Composer.

Depuis la racine du dépôt, lance

```sh
docker compose up --build
```

Ouvre [la carte du restaurant](http://127.0.0.1:8080/). Le démarrage attend MySQL 8.4, applique les migrations et initialise les données de démonstration. Les données MySQL persistent dans le volume Compose. Le port web est publié sur la boucle locale.

## Comptes de démonstration

Les trois comptes utilisent le mot de passe de démonstration `RestaurantDemo!2026`. Pour le remplacer lors de la première installation, définis la variable d’environnement `DEMO_PASSWORD` avant le premier démarrage.

| Identifiant | Écrans |
|---|---|
| `gerant` | Tableau de bord, administration, salle et cuisine |
| `serveur` | Prise de commandes en salle |
| `cuisine` | Liste des plats en cours et changements de statut |

Connecte-toi sur [Connexion équipe](http://127.0.0.1:8080/connexion).

## Sans Docker

Prépare PHP 8.5 avec les extensions `intl` et `pdo_mysql`, Composer et une base MySQL 8.4 avec un utilisateur dédié. Copie `.env.example` dans `.env.local` et renseigne `DATABASE_URL`.

```sh
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --append --no-interaction
php -S 127.0.0.1:8000 -t public
```

Ouvre [la carte locale](http://127.0.0.1:8000/). L’option `--append` préserve les données présentes. Le jeu complet est identifié par le menu « Menu du marché ».

## Vérification et dépannage

- **Port occupé** · Lance `RESTAURANT_PORT=8081 docker compose up --build`, puis ouvre le port 8081.
- **Démarrage lent ou erreur MySQL** · Consulte `docker compose logs database application` et `docker compose ps`. La première construction peut prendre plusieurs minutes.
- **Les données Open Food Facts restent à vérifier** · Associe le code-barres depuis le tableau de bord, valide l’information après contrôle de l’emballage et sélectionne la fiche sur une ligne d’achat avant de réceptionner l’achat.
- **Tests métier** · Lance `docker compose exec application php tests/restaurant.php`, ou `php tests/restaurant.php` sur une base locale migrée. Le scénario utilise une transaction annulée à la fin.
- **Arrêt** · Lance `docker compose down`. Le volume MySQL est conservé.
