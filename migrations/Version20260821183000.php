<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la catégorie Recompilations et y classe le portage PC de Super Mario 64.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO categorie_jeu (nom, slug) SELECT 'Recompilations', 'recompilations' WHERE NOT EXISTS (SELECT 1 FROM categorie_jeu WHERE slug = 'recompilations')");
        $this->addSql("UPDATE jeu SET categorie_id = (SELECT id FROM categorie_jeu WHERE slug = 'recompilations') WHERE slug = 'super-mario-64-portage-pc-multiplateformes'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE jeu SET categorie_id = (SELECT id FROM categorie_jeu WHERE slug = 'fan-games') WHERE slug = 'super-mario-64-portage-pc-multiplateformes'");
        $this->addSql("DELETE FROM categorie_jeu WHERE slug = 'recompilations'");
    }
}
