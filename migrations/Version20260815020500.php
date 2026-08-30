<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815020500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne la table publication avec les métadonnées Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication CHANGE publiee_le publiee_le DATETIME NOT NULL');
        $this->addSql('ALTER TABLE publication RENAME INDEX IDX_PUBLICATION_AUTEUR TO IDX_AF3C677960BB6FE6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE publication CHANGE publiee_le publiee_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE publication RENAME INDEX IDX_AF3C677960BB6FE6 TO IDX_PUBLICATION_AUTEUR');
    }
}
