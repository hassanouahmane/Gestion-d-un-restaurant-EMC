<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109173127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les comptes, le suivi des plats, les inventaires et les produits achetés.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE compte (identifiant VARCHAR(80) NOT NULL, role VARCHAR(24) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY (identifiant)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inventaire (id INT AUTO_INCREMENT NOT NULL, date_comptage DATETIME NOT NULL, stock_theorique NUMERIC(14, 4) NOT NULL, stock_compte NUMERIC(14, 4) NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_338920E0933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat_service (id INT AUTO_INCREMENT NOT NULL, quantite SMALLINT NOT NULL, statut VARCHAR(24) NOT NULL, besoins JSON NOT NULL, prix_vente_unitaire NUMERIC(12, 2) DEFAULT NULL, cout_unitaire NUMERIC(12, 2) DEFAULT NULL, ligne_commande_id INT NOT NULL, plat_id INT NOT NULL, INDEX IDX_66CC1A35E10FEE63 (ligne_commande_id), INDEX IDX_66CC1A35D73DB560 (plat_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit_achete (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, code_barres VARCHAR(14) NOT NULL, allergenes JSON DEFAULT NULL, etat VARCHAR(20) NOT NULL, ingredient_id INT NOT NULL, UNIQUE INDEX UNIQ_9DAF44F968EF62E0 (code_barres), INDEX IDX_9DAF44F9933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE inventaire ADD CONSTRAINT FK_338920E0933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE plat_service ADD CONSTRAINT FK_66CC1A35E10FEE63 FOREIGN KEY (ligne_commande_id) REFERENCES ligne_commande (id)');
        $this->addSql('ALTER TABLE plat_service ADD CONSTRAINT FK_66CC1A35D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE produit_achete ADD CONSTRAINT FK_9DAF44F9933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE ligne_achat ADD produit_achete_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_achat ADD CONSTRAINT FK_25056E66E28B98A FOREIGN KEY (produit_achete_id) REFERENCES produit_achete (id)');
        $this->addSql('CREATE INDEX IDX_25056E66E28B98A ON ligne_achat (produit_achete_id)');
        $this->addSql("ALTER TABLE plat_service ADD CONSTRAINT chk_plat_service_quantite CHECK (quantite > 0), ADD CONSTRAINT chk_plat_service_statut CHECK (statut IN ('a_preparer', 'en_cours', 'pret', 'servi', 'annule'))");
        $this->addSql('ALTER TABLE inventaire ADD CONSTRAINT chk_inventaire_stock CHECK (stock_compte >= 0)');
        $this->addSql("ALTER TABLE produit_achete ADD CONSTRAINT chk_produit_achete_etat CHECK (etat IN ('a_verifier', 'confirme') AND (etat <> 'confirme' OR allergenes IS NOT NULL))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventaire DROP FOREIGN KEY FK_338920E0933FE08C');
        $this->addSql('ALTER TABLE plat_service DROP FOREIGN KEY FK_66CC1A35E10FEE63');
        $this->addSql('ALTER TABLE plat_service DROP FOREIGN KEY FK_66CC1A35D73DB560');
        $this->addSql('ALTER TABLE produit_achete DROP FOREIGN KEY FK_9DAF44F9933FE08C');
        $this->addSql('DROP TABLE compte');
        $this->addSql('DROP TABLE inventaire');
        $this->addSql('DROP TABLE plat_service');
        $this->addSql('ALTER TABLE ligne_achat DROP FOREIGN KEY FK_25056E66E28B98A');
        $this->addSql('DROP INDEX IDX_25056E66E28B98A ON ligne_achat');
        $this->addSql('ALTER TABLE ligne_achat DROP produit_achete_id');
        $this->addSql('DROP TABLE produit_achete');
    }
}
