<?php

declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815031000 extends AbstractMigration
{
    public function getDescription(): string { return 'Profil public enrichi et abonnements entre membres'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD banniere VARCHAR(255) DEFAULT NULL, ADD biographie LONGTEXT DEFAULT NULL, ADD localisation VARCHAR(100) DEFAULT NULL, ADD statut_profil VARCHAR(160) DEFAULT NULL, ADD date_naissance DATE DEFAULT NULL, ADD inscrit_le DATETIME DEFAULT NULL');
        $this->addSql('UPDATE utilisateur SET inscrit_le = NOW()');
        $this->addSql('ALTER TABLE utilisateur CHANGE inscrit_le inscrit_le DATETIME NOT NULL');
        $this->addSql('CREATE TABLE utilisateur_abonnement (abonne_id INT NOT NULL, suivi_id INT NOT NULL, INDEX IDX_7AAAAADC325A696 (abonne_id), INDEX IDX_7AAAAAD7FEA59C0 (suivi_id), PRIMARY KEY(abonne_id, suivi_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_abonnement ADD CONSTRAINT FK_7AAAAADC325A696 FOREIGN KEY (abonne_id) REFERENCES utilisateur (id) ON DELETE CASCADE, ADD CONSTRAINT FK_7AAAAAD7FEA59C0 FOREIGN KEY (suivi_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE utilisateur_abonnement');
        $this->addSql('ALTER TABLE utilisateur DROP banniere, DROP biographie, DROP localisation, DROP statut_profil, DROP date_naissance, DROP inscrit_le');
    }
}
