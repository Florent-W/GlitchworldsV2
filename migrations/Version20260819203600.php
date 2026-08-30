<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819203600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ferme la parenthèse manquante sur les notifications de succès.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE notification
            SET message = CONCAT(message, ')')
            WHERE titre = 'Succès débloqué'
              AND message LIKE '%(+_% points'
              AND RIGHT(message, 1) <> ')'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE notification
            SET message = LEFT(message, CHAR_LENGTH(message) - 1)
            WHERE titre = 'Succès débloqué'
              AND message LIKE '%(+_% points)'
        SQL);
    }
}
