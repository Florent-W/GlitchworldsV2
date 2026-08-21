<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821213000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute cinq cadres d’avatar achetables et équipables.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD cadre_avatar_equipe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_UTILISATEUR_CADRE_AVATAR FOREIGN KEY (cadre_avatar_equipe_id) REFERENCES article_boutique (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_UTILISATEUR_CADRE_AVATAR ON utilisateur (cadre_avatar_equipe_id)');
        $this->addSql(<<<'SQL'
            INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES
                ('Cadre pixel', 'cadre-avatar-pixel', 'Un contour carré et pixelisé autour de ton avatar.', 150, 'cadre', 'grid-3x3-gap-fill', 'secondary', 1, NULL),
                ('Cadre glitch', 'cadre-avatar-glitch', 'Une séparation cyan et rouge inspirée des anomalies visuelles.', 300, 'cadre', 'activity', 'danger', 1, NULL),
                ('Cadre néon', 'cadre-avatar-neon', 'Un anneau lumineux bleu et violet autour de ton avatar.', 450, 'cadre', 'lightbulb-fill', 'primary', 1, NULL),
                ('Cadre rétro', 'cadre-avatar-retro', 'Un double contour aux couleurs des écrans et consoles rétro.', 550, 'cadre', 'controller', 'info', 1, NULL),
                ('Cadre doré', 'cadre-avatar-dore', 'Une finition dorée prestigieuse pour ton avatar.', 900, 'cadre', 'award-fill', 'warning', 1, NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_UTILISATEUR_CADRE_AVATAR');
        $this->addSql('DROP INDEX IDX_UTILISATEUR_CADRE_AVATAR ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP cadre_avatar_equipe_id');
        $this->addSql("DELETE FROM article_boutique WHERE slug IN ('cadre-avatar-pixel', 'cadre-avatar-glitch', 'cadre-avatar-neon', 'cadre-avatar-retro', 'cadre-avatar-dore')");
    }
}
