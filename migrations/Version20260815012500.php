<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815012500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les noms des index des réactions aux commentaires de jeux avec Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_jeu_aime RENAME INDEX IDX_CJA_COMMENTAIRE TO IDX_C72DEC18981AE9A9');
        $this->addSql('ALTER TABLE commentaire_jeu_aime RENAME INDEX IDX_CJA_UTILISATEUR TO IDX_C72DEC18FB88E14F');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_jeu_aime RENAME INDEX IDX_C72DEC18981AE9A9 TO IDX_CJA_COMMENTAIRE');
        $this->addSql('ALTER TABLE commentaire_jeu_aime RENAME INDEX IDX_C72DEC18FB88E14F TO IDX_CJA_UTILISATEUR');
    }
}
