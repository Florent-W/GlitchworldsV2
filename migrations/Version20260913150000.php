<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913150000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute le partage public et les métadonnées SEO aux listes de jeux.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE liste_jeux ADD slug VARCHAR(100) DEFAULT NULL, ADD publique TINYINT(1) DEFAULT 0 NOT NULL, ADD modifie_le DATETIME NOT NULL');
        $this->addSql('UPDATE liste_jeux SET modifie_le = cree_le');
        $this->addSql('CREATE INDEX liste_jeux_publique_idx ON liste_jeux (publique, modifie_le)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX liste_jeux_publique_idx ON liste_jeux');
        $this->addSql('ALTER TABLE liste_jeux DROP slug, DROP publique, DROP modifie_le');
    }
}
