<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute de nouveaux titres de profil à la boutique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES
                ('Nouveau joueur', 'nouveau-joueur', 'Le titre idéal pour faire ses premiers pas dans la communauté.', 50, 'titre', 'joystick', 'secondary', 1, NULL),
                ('Archiviste du pixel', 'archiviste-du-pixel', 'Pour celles et ceux qui préservent la mémoire du jeu vidéo.', 200, 'titre', 'archive-fill', 'info', 1, NULL),
                ('Maître du speedrun', 'maitre-du-speedrun', 'Chaque seconde compte pour atteindre le meilleur temps.', 400, 'titre', 'stopwatch-fill', 'warning', 1, NULL),
                ('Architecte de mondes', 'architecte-de-mondes', 'Un titre réservé aux bâtisseurs d’univers mémorables.', 650, 'titre', 'boxes', 'success', 1, NULL),
                ('Légende du glitch', 'legende-du-glitch', 'Le titre prestigieux des membres qui ont marqué Glitchworlds.', 1000, 'titre', 'lightning-charge-fill', 'danger', 1, NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM article_boutique WHERE slug IN ('nouveau-joueur', 'archiviste-du-pixel', 'maitre-du-speedrun', 'architecte-de-mondes', 'legende-du-glitch')");
    }
}
