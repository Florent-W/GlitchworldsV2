<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822022300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Calibre les points des succès de niveau pour coller aux paliers de rareté.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE succes SET points = 150 WHERE code = 'niveau_10'");
        $this->addSql("UPDATE succes SET points = 250 WHERE code = 'niveau_20'");
        $this->addSql("UPDATE succes SET points = 400 WHERE code = 'niveau_50'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE succes SET points = 0 WHERE code IN ('niveau_10', 'niveau_20', 'niveau_50')");
    }
}
