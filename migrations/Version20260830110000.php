<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi des avertissements et suppressions de comptes inactifs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD inactivite_avertie_le DATETIME DEFAULT NULL, ADD suppression_programmee_le DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX utilisateur_inactivite_idx ON utilisateur (derniere_activite, inactivite_avertie_le, suppression_programmee_le)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX utilisateur_inactivite_idx ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP inactivite_avertie_le, DROP suppression_programmee_le');
    }
}
