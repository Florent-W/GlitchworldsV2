<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Retire le titre Glitcheur débutant de la boutique.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM article_boutique WHERE slug = 'glitcheur-debutant'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES ('Glitcheur débutant', 'glitcheur-debutant', 'Les premières anomalies commencent déjà à apparaître.', 75, 'titre', 'activity', 'info', 1, NULL)");
    }
}
