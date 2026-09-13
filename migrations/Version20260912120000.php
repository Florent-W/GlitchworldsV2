<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260912120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute un ordre persistant aux jeux des listes personnalisées'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE liste_jeux_element ADD position INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE liste_jeux_element SET position = jeu_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE liste_jeux_element DROP position');
    }
}
