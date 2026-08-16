<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816000000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute l’expérience et les points aux membres'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD experience INT DEFAULT 0 NOT NULL, ADD points INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP experience, DROP points');
    }
}
