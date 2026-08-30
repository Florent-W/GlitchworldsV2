<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme le succès de création de la première liste.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE succes SET nom = 'Première liste' WHERE code = 'premiere_liste'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE succes SET nom = 'Mise en rayon' WHERE code = 'premiere_liste'");
    }
}
