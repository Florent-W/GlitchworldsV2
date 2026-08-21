<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute cinq titres de profil inspirés de l’univers Glitchworlds.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES
                ('Glitcheur débutant', 'glitcheur-debutant', 'Les premières anomalies commencent déjà à apparaître.', 75, 'titre', 'activity', 'info', 1, NULL),
                ('Joueur rétro', 'joueur-retro', 'Toujours prêt à ressortir une console et quelques pixels.', 175, 'titre', 'dpad-fill', 'secondary', 1, NULL),
                ('Maître des secrets', 'maitre-des-secrets', 'Aucun passage caché ni code secret ne lui échappe.', 300, 'titre', 'key-fill', 'warning', 1, NULL),
                ('Hacker de pixels', 'hacker-de-pixels', 'Il détourne les règles du jeu, un pixel à la fois.', 600, 'titre', 'terminal-fill', 'success', 1, NULL),
                ('Réécrivain de réalité', 'reecrivain-de-realite', 'Le monde n’est qu’un programme qui attend d’être réécrit.', 900, 'titre', 'braces-asterisk', 'danger', 1, NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM article_boutique WHERE slug IN ('glitcheur-debutant', 'joueur-retro', 'maitre-des-secrets', 'hacker-de-pixels', 'reecrivain-de-realite')");
    }
}
