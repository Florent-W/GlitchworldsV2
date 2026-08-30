<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rattache aux jeux et actualités les vues auparavant enregistrées comme pages.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE vue_page SET type_contenu = 'jeu', contenu_id = CAST(SUBSTRING_INDEX(chemin, '-', -1) AS UNSIGNED) WHERE type_contenu = 'page' AND chemin REGEXP '^/jeu/[a-z0-9-]+-[0-9]+$'");
        $this->addSql("UPDATE vue_page SET type_contenu = 'actualite', contenu_id = CAST(SUBSTRING_INDEX(chemin, '-', -1) AS UNSIGNED) WHERE type_contenu = 'page' AND chemin REGEXP '^/actualite/[a-z0-9-]+-[0-9]+$'");
    }

    public function down(Schema $schema): void
    {
        // Les lignes corrigées ne peuvent pas être distinguées des vues correctement enregistrées ensuite.
    }
}
