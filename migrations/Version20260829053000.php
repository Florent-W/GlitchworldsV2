<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260829053000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Réserve developpeur au créateur du jeu et conserve séparément l’auteur de fiche importé.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu CHANGE developpeur auteur_presentation_legacy VARCHAR(120) DEFAULT NULL, CHANGE studio_developpeur developpeur VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu CHANGE developpeur studio_developpeur VARCHAR(160) DEFAULT NULL, CHANGE auteur_presentation_legacy developpeur VARCHAR(120) DEFAULT NULL');
    }
}
