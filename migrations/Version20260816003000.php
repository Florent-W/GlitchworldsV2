<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la galerie d’images aux fiches de jeux';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu ADD galerie JSON DEFAULT NULL');
        $this->addSql('UPDATE jeu SET galerie = JSON_ARRAY() WHERE galerie IS NULL');
        $this->addSql('ALTER TABLE jeu MODIFY galerie JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu DROP galerie');
    }
}
