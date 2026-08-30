<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Retire trois titres de profil de la boutique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM article_boutique WHERE slug IN ('archiviste-du-pixel', 'chasseur-de-bugs', 'architecte-de-mondes')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES
                ('Archiviste du pixel', 'archiviste-du-pixel', 'Pour celles et ceux qui préservent la mémoire du jeu vidéo.', 200, 'titre', 'archive-fill', 'info', 1, NULL),
                ('Chasseur de bugs', 'chasseur-de-bugs', 'Affiche ton goût pour les secrets et les anomalies.', 250, 'titre', 'bug-fill', 'danger', 1, NULL),
                ('Architecte de mondes', 'architecte-de-mondes', 'Un titre réservé aux bâtisseurs d’univers mémorables.', 650, 'titre', 'boxes', 'success', 1, NULL)
        SQL);
    }
}
