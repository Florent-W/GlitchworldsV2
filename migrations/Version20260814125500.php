<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814125500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les rôles des utilisateurs importés en tableau JSON vide.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE utilisateur SET roles = JSON_ARRAY() WHERE JSON_TYPE(roles) = 'NULL'");
    }

    public function down(Schema $schema): void
    {
    }
}
