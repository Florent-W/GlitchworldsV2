<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814171500 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les actualités.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE actualite (id INT AUTO_INCREMENT NOT NULL, auteur_id INT DEFAULT NULL, titre VARCHAR(255) NOT NULL, slug VARCHAR(180) NOT NULL, description VARCHAR(160) NOT NULL, contenu LONGTEXT NOT NULL, categorie VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, miniature VARCHAR(255) DEFAULT NULL, publiee_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_17DB3F342B36786B (slug), INDEX IDX_17DB3F3460BB6FE6 (auteur_id), INDEX actualite_publique_idx (statut, publiee_le), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB");
        $this->addSql('ALTER TABLE actualite ADD CONSTRAINT FK_17DB3F3460BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE actualite');
    }
}
