<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819174500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rattrape l’XP manquante des succès déjà débloqués (les points ne faisaient pas monter de niveau).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE utilisateur u
            SET experience = experience + COALESCE((
                SELECT SUM(m.points)
                FROM mouvement_progression m
                WHERE m.utilisateur_id = u.id
                  AND m.categorie = 'succes'
                  AND m.points > 0
            ), 0)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE utilisateur u
            SET experience = GREATEST(0, experience - COALESCE((
                SELECT SUM(m.points)
                FROM mouvement_progression m
                WHERE m.utilisateur_id = u.id
                  AND m.categorie = 'succes'
                  AND m.points > 0
            ), 0))
        SQL);
    }
}
