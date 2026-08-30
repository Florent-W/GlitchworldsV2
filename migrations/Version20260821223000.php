<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rend les emplacements de vitrine cumulables et autorise plusieurs fiches mises en avant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE achat_boutique ADD quantite INT DEFAULT 1 NOT NULL');
        $this->addSql('CREATE TABLE utilisateur_fiche_vitrine (utilisateur_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_VITRINE_UTILISATEUR (utilisateur_id), INDEX IDX_VITRINE_JEU (jeu_id), PRIMARY KEY(utilisateur_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_fiche_vitrine ADD CONSTRAINT FK_VITRINE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_fiche_vitrine ADD CONSTRAINT FK_VITRINE_JEU FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('INSERT IGNORE INTO utilisateur_fiche_vitrine (utilisateur_id, jeu_id) SELECT id, fiche_mise_en_avant_id FROM utilisateur WHERE fiche_mise_en_avant_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE utilisateur_fiche_vitrine');
        $this->addSql('ALTER TABLE achat_boutique DROP quantite');
    }
}
