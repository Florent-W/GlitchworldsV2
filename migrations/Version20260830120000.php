<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime l’adresse e-mail legacy devenue inutile.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP email_legacy');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD email_legacy VARCHAR(180) DEFAULT NULL');
    }
}
