<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Impose la finalisation des comptes Google qui n’ont pas encore accepté les conditions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD finalisation_oauth_requise TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE utilisateur u INNER JOIN identite_oauth i ON i.utilisateur_id = u.id SET u.finalisation_oauth_requise = 1 WHERE u.conditions_acceptees_le IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP finalisation_oauth_requise');
    }
}
