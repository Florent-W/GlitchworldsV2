<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822023000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Met à jour l’icône et la description de l’effet Distorsion glitch.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE article_boutique SET icone = 'intersect', description = 'Des artefacts cyan et rose font vaciller l’en-tête de ton profil.' WHERE slug = 'effet-distorsion-glitch'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE article_boutique SET icone = 'activity', description = 'Une pulsation glitch rare souligne ton identité.' WHERE slug = 'effet-distorsion-glitch'");
    }
}
