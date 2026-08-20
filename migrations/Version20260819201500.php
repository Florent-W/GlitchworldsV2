<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819201500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige le libellé des notifications de succès (sans tiret séparateur).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE notification
            SET message = CONCAT(REPLACE(REPLACE(message, ' — +', ' (+'), ' - +', ' (+'), ')')
            WHERE titre = 'Succès débloqué'
              AND (message LIKE '% — +%' OR message LIKE '% - +%')
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE notification
            SET message = REPLACE(message, ' (+', ' - +')
            WHERE titre = 'Succès débloqué'
              AND message LIKE '% (+'
        SQL);
    }
}
