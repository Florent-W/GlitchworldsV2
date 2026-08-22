<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822023300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Passe l’icône Distorsion glitch sur soundwave.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE article_boutique SET icone = 'soundwave' WHERE slug = 'effet-distorsion-glitch'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE article_boutique SET icone = 'intersect' WHERE slug = 'effet-distorsion-glitch'");
    }
}
