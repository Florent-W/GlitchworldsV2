<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enregistre la date et la version des conditions acceptées lors de l’inscription Google.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD conditions_acceptees_le DATETIME DEFAULT NULL, ADD version_conditions VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP conditions_acceptees_le, DROP version_conditions');
    }
}
