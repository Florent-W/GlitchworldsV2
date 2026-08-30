<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la bannière aux actualités.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite ADD banniere VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite DROP banniere');
    }
}
