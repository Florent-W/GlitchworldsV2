<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821220000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute la Vitrine de créateur cosmétique aux profils.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD fiche_mise_en_avant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_UTILISATEUR_FICHE_VITRINE FOREIGN KEY (fiche_mise_en_avant_id) REFERENCES jeu (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_UTILISATEUR_FICHE_VITRINE ON utilisateur (fiche_mise_en_avant_id)');
        $this->addSql("INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES ('Vitrine de créateur', 'vitrine-de-createur', 'Présente l’une de tes fiches approuvées dans un encadrement spécial sur ton profil.', 700, 'vitrine', 'stars', 'warning', 1, NULL)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_UTILISATEUR_FICHE_VITRINE');
        $this->addSql('DROP INDEX IDX_UTILISATEUR_FICHE_VITRINE ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP fiche_mise_en_avant_id');
        $this->addSql("DELETE FROM article_boutique WHERE slug = 'vitrine-de-createur'");
    }
}
