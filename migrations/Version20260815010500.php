<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815010500 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne les index des mentions J’aime avec Doctrine.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_actualite_aime RENAME INDEX IDX_CAA_COMMENTAIRE TO IDX_A189796951D0418A');
        $this->addSql('ALTER TABLE commentaire_actualite_aime RENAME INDEX IDX_CAA_UTILISATEUR TO IDX_A1897969FB88E14F');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_actualite_aime RENAME INDEX IDX_A189796951D0418A TO IDX_CAA_COMMENTAIRE');
        $this->addSql('ALTER TABLE commentaire_actualite_aime RENAME INDEX IDX_A1897969FB88E14F TO IDX_CAA_UTILISATEUR');
    }
}
