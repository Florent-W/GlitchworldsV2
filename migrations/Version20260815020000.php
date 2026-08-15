<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les publications du fil communautaire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE publication (id INT AUTO_INCREMENT NOT NULL, auteur_id INT DEFAULT NULL, contenu LONGTEXT NOT NULL, publiee_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_PUBLICATION_AUTEUR (auteur_id), INDEX publication_date_idx (publiee_le), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB");
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_PUBLICATION_AUTEUR FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE publication');
    }
}
