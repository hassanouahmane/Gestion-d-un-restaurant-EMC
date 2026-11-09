<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109162652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables du restaurant et leurs contraintes d’intégrité.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE achat (id INT AUTO_INCREMENT NOT NULL, date_achat DATE NOT NULL, statut VARCHAR(24) NOT NULL, fournisseur_id INT NOT NULL, INDEX IDX_26A98456670C757F (fournisseur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date_commande DATETIME NOT NULL, statut VARCHAR(24) DEFAULT \'brouillon\' NOT NULL, table_salle_id INT NOT NULL, INDEX IDX_6EEAA67D4C8D6EE5 (table_salle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dose_ingredient (id INT AUTO_INCREMENT NOT NULL, quantite NUMERIC(14, 4) NOT NULL, unite VARCHAR(16) NOT NULL, recette_id INT NOT NULL, ingredient_id INT NOT NULL, UNIQUE INDEX UNIQ_11742A0E89312FE9933FE08C (recette_id, ingredient_id), INDEX IDX_11742A0E89312FE9 (recette_id), INDEX IDX_11742A0E933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dose_preparation (id INT AUTO_INCREMENT NOT NULL, quantite NUMERIC(14, 4) NOT NULL, unite VARCHAR(16) NOT NULL, recette_id INT NOT NULL, preparation_id INT NOT NULL, UNIQUE INDEX UNIQ_4E372D9D89312FE93DD9B8BA (recette_id, preparation_id), INDEX IDX_4E372D9D89312FE9 (recette_id), INDEX IDX_4E372D9D3DD9B8BA (preparation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fournisseur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE groupe_choix (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(80) NOT NULL, menu_id INT NOT NULL, INDEX IDX_1AE54EC1CCD7E912 (menu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ingredient (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, unite_stock VARCHAR(16) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_achat (id INT AUTO_INCREMENT NOT NULL, quantite NUMERIC(14, 4) NOT NULL, unite_achat VARCHAR(16) NOT NULL, prix_unitaire NUMERIC(12, 2) NOT NULL, achat_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_25056E66FE95D117 (achat_id), INDEX IDX_25056E66933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_commande (id INT AUTO_INCREMENT NOT NULL, quantite SMALLINT NOT NULL, prix_unitaire_applique NUMERIC(12, 2) NOT NULL, commande_id INT NOT NULL, plat_id INT DEFAULT NULL, menu_id INT DEFAULT NULL, INDEX IDX_3170B74B82EA2E54 (commande_id), INDEX IDX_3170B74BD73DB560 (plat_id), INDEX IDX_3170B74BCCD7E912 (menu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, prix_vente NUMERIC(12, 2) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mouvement_stock (id INT AUTO_INCREMENT NOT NULL, date_mouvement DATETIME NOT NULL, type VARCHAR(16) NOT NULL, quantite NUMERIC(14, 4) NOT NULL, motif VARCHAR(150) NOT NULL, ingredient_id INT NOT NULL, ligne_achat_id INT DEFAULT NULL, INDEX IDX_61E2C8EB933FE08C (ingredient_id), INDEX IDX_61E2C8EBA10FC021 (ligne_achat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE option_menu (id INT AUTO_INCREMENT NOT NULL, groupe_choix_id INT NOT NULL, plat_id INT NOT NULL, UNIQUE INDEX UNIQ_950B907048B64D21D73DB560 (groupe_choix_id, plat_id), INDEX IDX_950B907048B64D21 (groupe_choix_id), INDEX IDX_950B9070D73DB560 (plat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, prix_vente NUMERIC(12, 2) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat_choisi (id INT AUTO_INCREMENT NOT NULL, ligne_commande_id INT NOT NULL, groupe_choix_id INT NOT NULL, plat_id INT NOT NULL, UNIQUE INDEX UNIQ_DBFC3179E10FEE6348B64D21 (ligne_commande_id, groupe_choix_id), INDEX IDX_DBFC3179E10FEE63 (ligne_commande_id), INDEX IDX_DBFC317948B64D21 (groupe_choix_id), INDEX IDX_DBFC3179D73DB560 (plat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE preparation (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recette (id INT AUTO_INCREMENT NOT NULL, rendement NUMERIC(14, 4) NOT NULL, unite_rendement VARCHAR(16) NOT NULL, plat_id INT DEFAULT NULL, preparation_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_49BB6390D73DB560 (plat_id), UNIQUE INDEX UNIQ_49BB63903DD9B8BA (preparation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE table_salle (id INT AUTO_INCREMENT NOT NULL, numero VARCHAR(20) NOT NULL, capacite SMALLINT NOT NULL, UNIQUE INDEX UNIQ_426BB324F55AE19E (numero), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE achat ADD CONSTRAINT FK_26A98456670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D4C8D6EE5 FOREIGN KEY (table_salle_id) REFERENCES table_salle (id)');
        $this->addSql('ALTER TABLE dose_ingredient ADD CONSTRAINT FK_11742A0E89312FE9 FOREIGN KEY (recette_id) REFERENCES recette (id)');
        $this->addSql('ALTER TABLE dose_ingredient ADD CONSTRAINT FK_11742A0E933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE dose_preparation ADD CONSTRAINT FK_4E372D9D89312FE9 FOREIGN KEY (recette_id) REFERENCES recette (id)');
        $this->addSql('ALTER TABLE dose_preparation ADD CONSTRAINT FK_4E372D9D3DD9B8BA FOREIGN KEY (preparation_id) REFERENCES preparation (id)');
        $this->addSql('ALTER TABLE groupe_choix ADD CONSTRAINT FK_1AE54EC1CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE ligne_achat ADD CONSTRAINT FK_25056E66FE95D117 FOREIGN KEY (achat_id) REFERENCES achat (id)');
        $this->addSql('ALTER TABLE ligne_achat ADD CONSTRAINT FK_25056E66933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BD73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BCCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EB933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBA10FC021 FOREIGN KEY (ligne_achat_id) REFERENCES ligne_achat (id)');
        $this->addSql('ALTER TABLE option_menu ADD CONSTRAINT FK_950B907048B64D21 FOREIGN KEY (groupe_choix_id) REFERENCES groupe_choix (id)');
        $this->addSql('ALTER TABLE option_menu ADD CONSTRAINT FK_950B9070D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE plat_choisi ADD CONSTRAINT FK_DBFC3179E10FEE63 FOREIGN KEY (ligne_commande_id) REFERENCES ligne_commande (id)');
        $this->addSql('ALTER TABLE plat_choisi ADD CONSTRAINT FK_DBFC317948B64D21 FOREIGN KEY (groupe_choix_id) REFERENCES groupe_choix (id)');
        $this->addSql('ALTER TABLE plat_choisi ADD CONSTRAINT FK_DBFC3179D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT FK_49BB6390D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT FK_49BB63903DD9B8BA FOREIGN KEY (preparation_id) REFERENCES preparation (id)');

        $this->addSql('ALTER TABLE plat ADD CONSTRAINT chk_plat_prix CHECK (prix_vente >= 0)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT chk_menu_prix CHECK (prix_vente >= 0)');
        $this->addSql('ALTER TABLE recette ADD CONSTRAINT chk_recette_rendement CHECK (rendement > 0), ADD CONSTRAINT chk_recette_proprietaire CHECK ((plat_id IS NULL) <> (preparation_id IS NULL))');
        $this->addSql('ALTER TABLE dose_ingredient ADD CONSTRAINT chk_dose_ingredient_quantite CHECK (quantite > 0)');
        $this->addSql('ALTER TABLE dose_preparation ADD CONSTRAINT chk_dose_preparation_quantite CHECK (quantite > 0)');
        $this->addSql('ALTER TABLE table_salle ADD CONSTRAINT chk_table_salle_capacite CHECK (capacite > 0)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT chk_ligne_commande_quantite CHECK (quantite > 0), ADD CONSTRAINT chk_ligne_commande_prix CHECK (prix_unitaire_applique >= 0), ADD CONSTRAINT chk_ligne_commande_article CHECK ((plat_id IS NULL) <> (menu_id IS NULL))');
        $this->addSql('ALTER TABLE ligne_achat ADD CONSTRAINT chk_ligne_achat_quantite CHECK (quantite > 0), ADD CONSTRAINT chk_ligne_achat_prix CHECK (prix_unitaire >= 0)');
        $this->addSql("ALTER TABLE mouvement_stock ADD CONSTRAINT chk_mouvement_stock_quantite CHECK (quantite > 0), ADD CONSTRAINT chk_mouvement_stock_type CHECK (type IN ('entree', 'sortie')), ADD CONSTRAINT chk_mouvement_stock_achat CHECK (ligne_achat_id IS NULL OR type = 'entree')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE achat DROP FOREIGN KEY FK_26A98456670C757F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D4C8D6EE5');
        $this->addSql('ALTER TABLE dose_ingredient DROP FOREIGN KEY FK_11742A0E89312FE9');
        $this->addSql('ALTER TABLE dose_ingredient DROP FOREIGN KEY FK_11742A0E933FE08C');
        $this->addSql('ALTER TABLE dose_preparation DROP FOREIGN KEY FK_4E372D9D89312FE9');
        $this->addSql('ALTER TABLE dose_preparation DROP FOREIGN KEY FK_4E372D9D3DD9B8BA');
        $this->addSql('ALTER TABLE groupe_choix DROP FOREIGN KEY FK_1AE54EC1CCD7E912');
        $this->addSql('ALTER TABLE ligne_achat DROP FOREIGN KEY FK_25056E66FE95D117');
        $this->addSql('ALTER TABLE ligne_achat DROP FOREIGN KEY FK_25056E66933FE08C');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BD73DB560');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BCCD7E912');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EB933FE08C');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBA10FC021');
        $this->addSql('ALTER TABLE option_menu DROP FOREIGN KEY FK_950B907048B64D21');
        $this->addSql('ALTER TABLE option_menu DROP FOREIGN KEY FK_950B9070D73DB560');
        $this->addSql('ALTER TABLE plat_choisi DROP FOREIGN KEY FK_DBFC3179E10FEE63');
        $this->addSql('ALTER TABLE plat_choisi DROP FOREIGN KEY FK_DBFC317948B64D21');
        $this->addSql('ALTER TABLE plat_choisi DROP FOREIGN KEY FK_DBFC3179D73DB560');
        $this->addSql('ALTER TABLE recette DROP FOREIGN KEY FK_49BB6390D73DB560');
        $this->addSql('ALTER TABLE recette DROP FOREIGN KEY FK_49BB63903DD9B8BA');
        $this->addSql('DROP TABLE achat');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE dose_ingredient');
        $this->addSql('DROP TABLE dose_preparation');
        $this->addSql('DROP TABLE fournisseur');
        $this->addSql('DROP TABLE groupe_choix');
        $this->addSql('DROP TABLE ingredient');
        $this->addSql('DROP TABLE ligne_achat');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE mouvement_stock');
        $this->addSql('DROP TABLE option_menu');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE plat_choisi');
        $this->addSql('DROP TABLE preparation');
        $this->addSql('DROP TABLE recette');
        $this->addSql('DROP TABLE table_salle');
    }
}
