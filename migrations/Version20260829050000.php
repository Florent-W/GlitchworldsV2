<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute un développeur de jeu distinct de l’auteur historique de la fiche.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu ADD studio_developpeur VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu DROP studio_developpeur');
    }
}
