<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stocke de façon chiffrée la connexion à Google Search Console.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE connexion_search_console (id INT AUTO_INCREMENT NOT NULL, jeton_acces LONGTEXT NOT NULL, jeton_rafraichissement LONGTEXT DEFAULT NULL, expire_le DATETIME NOT NULL, connecte_le DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE connexion_search_console');
    }
}
