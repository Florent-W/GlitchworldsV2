<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permet de relier une fiche de mod à ses jeux associés.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mod_jeu (mod_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_MOD_JEU_MOD (mod_id), INDEX IDX_MOD_JEU_JEU (jeu_id), PRIMARY KEY(mod_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mod_jeu ADD CONSTRAINT FK_MOD_JEU_MOD FOREIGN KEY (mod_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mod_jeu ADD CONSTRAINT FK_MOD_JEU_JEU FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql(<<<'SQL'
            INSERT IGNORE INTO mod_jeu (mod_id, jeu_id)
            SELECT a.fiche_jeu_id, aj.jeu_id
            FROM actualite a
            INNER JOIN actualite_jeu aj ON aj.actualite_id = a.id
            WHERE a.categorie = 'mods'
              AND a.fiche_jeu_id IS NOT NULL
              AND aj.jeu_id != a.fiche_jeu_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE mod_jeu');
    }
}
