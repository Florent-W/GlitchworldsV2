<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime le type de présentation obsolète des actualités et normalise les noms des index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite DROP type_presentation');
        $this->addSql('ALTER TABLE actualite RENAME INDEX idx_actualite_fiche_jeu TO IDX_549281973A8294F9');
        $this->addSql('ALTER TABLE mod_jeu RENAME INDEX idx_mod_jeu_mod TO IDX_CD6D50A338E21CD');
        $this->addSql('ALTER TABLE mod_jeu RENAME INDEX idx_mod_jeu_jeu TO IDX_CD6D50A8C9E392E');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_utilisateur_effet_profil TO IDX_1D1C63B3AD570A6B');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_utilisateur_cadre_avatar TO IDX_1D1C63B3C37DEFC9');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX idx_utilisateur_fiche_vitrine TO IDX_1D1C63B3AB1EB8CC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE actualite ADD type_presentation VARCHAR(20) DEFAULT 'conteneur' NOT NULL");
        $this->addSql('ALTER TABLE actualite RENAME INDEX IDX_549281973A8294F9 TO idx_actualite_fiche_jeu');
        $this->addSql('ALTER TABLE mod_jeu RENAME INDEX IDX_CD6D50A338E21CD TO idx_mod_jeu_mod');
        $this->addSql('ALTER TABLE mod_jeu RENAME INDEX IDX_CD6D50A8C9E392E TO idx_mod_jeu_jeu');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX IDX_1D1C63B3AD570A6B TO idx_utilisateur_effet_profil');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX IDX_1D1C63B3C37DEFC9 TO idx_utilisateur_cadre_avatar');
        $this->addSql('ALTER TABLE utilisateur RENAME INDEX IDX_1D1C63B3AB1EB8CC TO idx_utilisateur_fiche_vitrine');
    }
}
