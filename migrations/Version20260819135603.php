<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819135603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les vidéos de fond sur les jeux et les préférences vidéo des utilisateurs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu ADD video_background VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD video_background_active TINYINT DEFAULT 1 NOT NULL, ADD video_background_sound_active TINYINT DEFAULT 0 NOT NULL, CHANGE notifications notifications JSON NOT NULL, CHANGE sessions_connectees sessions_connectees JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu DROP video_background');
        $this->addSql('ALTER TABLE utilisateur DROP video_background_active, DROP video_background_sound_active, CHANGE notifications notifications JSON DEFAULT \'_utf8mb4\\\\\'\'[]\\\\\'\'\' NOT NULL, CHANGE sessions_connectees sessions_connectees JSON DEFAULT \'_utf8mb4\\\\\'\'[]\\\\\'\'\' NOT NULL');
    }
}
