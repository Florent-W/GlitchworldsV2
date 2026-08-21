<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821210000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les effets de profil achetables et équipables.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD effet_profil_equipe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_UTILISATEUR_EFFET_PROFIL FOREIGN KEY (effet_profil_equipe_id) REFERENCES article_boutique (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_UTILISATEUR_EFFET_PROFIL ON utilisateur (effet_profil_equipe_id)');
        $this->addSql(<<<'SQL'
            INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES
                ('Aura néon', 'effet-aura-neon', 'Une lueur bleue et violette entoure ton profil.', 250, 'effet', 'lightbulb-fill', 'primary', 1, NULL),
                ('Scanlines rétro', 'effet-scanlines-retro', 'Un léger balayage CRT anime ta bannière de profil.', 350, 'effet', 'display-fill', 'secondary', 1, NULL),
                ('Hologramme', 'effet-hologramme', 'Des reflets holographiques parcourent doucement ton profil.', 550, 'effet', 'rainbow', 'info', 1, NULL),
                ('Distorsion glitch', 'effet-distorsion-glitch', 'Une pulsation glitch rare souligne ton identité.', 800, 'effet', 'activity', 'danger', 1, NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_UTILISATEUR_EFFET_PROFIL');
        $this->addSql('DROP INDEX IDX_UTILISATEUR_EFFET_PROFIL ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP effet_profil_equipe_id');
        $this->addSql("DELETE FROM article_boutique WHERE slug IN ('effet-aura-neon', 'effet-scanlines-retro', 'effet-hologramme', 'effet-distorsion-glitch')");
    }
}
