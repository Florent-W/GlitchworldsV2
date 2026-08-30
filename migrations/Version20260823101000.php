<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823101000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les noms des index du blocage et des nouvelles cibles de signalement sur le mapping Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement RENAME INDEX IDX_SIGNALEMENT_PROFIL TO IDX_F4B55114275ED078');
        $this->addSql('ALTER TABLE signalement RENAME INDEX IDX_SIGNALEMENT_AVIS TO IDX_F4B55114197E709F');
        $this->addSql('ALTER TABLE signalement RENAME INDEX IDX_SIGNALEMENT_MESSAGE TO IDX_F4B55114537A1329');
        $this->addSql('ALTER TABLE utilisateur_blocage RENAME INDEX IDX_BLOCAGE_BLOQUEUR TO IDX_E8F8CBD0C4A9DF7F');
        $this->addSql('ALTER TABLE utilisateur_blocage RENAME INDEX IDX_BLOCAGE_BLOQUE TO IDX_E8F8CBD0A929EBC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE signalement RENAME INDEX IDX_F4B55114275ED078 TO IDX_SIGNALEMENT_PROFIL');
        $this->addSql('ALTER TABLE signalement RENAME INDEX IDX_F4B55114197E709F TO IDX_SIGNALEMENT_AVIS');
        $this->addSql('ALTER TABLE signalement RENAME INDEX IDX_F4B55114537A1329 TO IDX_SIGNALEMENT_MESSAGE');
        $this->addSql('ALTER TABLE utilisateur_blocage RENAME INDEX IDX_E8F8CBD0C4A9DF7F TO IDX_BLOCAGE_BLOQUEUR');
        $this->addSql('ALTER TABLE utilisateur_blocage RENAME INDEX IDX_E8F8CBD0A929EBC TO IDX_BLOCAGE_BLOQUE');
    }
}
