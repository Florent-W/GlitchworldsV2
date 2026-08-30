<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le type de présentation aux jeux et actualités.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE jeu ADD type_presentation VARCHAR(20) DEFAULT 'conteneur' NOT NULL");
        $this->addSql("ALTER TABLE actualite ADD type_presentation VARCHAR(20) DEFAULT 'conteneur' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu DROP type_presentation');
        $this->addSql('ALTER TABLE actualite DROP type_presentation');
    }
}
