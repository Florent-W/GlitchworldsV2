<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822161500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les libellés stockés sur la graphie officielle « Glitchworlds ».';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE succes SET nom = REPLACE(nom, 'GlitchWorlds', 'Glitchworlds'), description = REPLACE(description, 'GlitchWorlds', 'Glitchworlds')");
        $this->addSql("UPDATE article_boutique SET nom = REPLACE(nom, 'GlitchWorlds', 'Glitchworlds'), description = REPLACE(description, 'GlitchWorlds', 'Glitchworlds')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE succes SET nom = REPLACE(nom, 'Glitchworlds', 'GlitchWorlds'), description = REPLACE(description, 'Glitchworlds', 'GlitchWorlds')");
        $this->addSql("UPDATE article_boutique SET nom = REPLACE(nom, 'Glitchworlds', 'GlitchWorlds'), description = REPLACE(description, 'Glitchworlds', 'GlitchWorlds')");
    }
}
