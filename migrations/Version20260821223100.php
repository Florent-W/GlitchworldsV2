<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821223100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harmonise les index de la sélection multiple des vitrines';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur_fiche_vitrine RENAME INDEX IDX_VITRINE_UTILISATEUR TO IDX_4D3763ECFB88E14F');
        $this->addSql('ALTER TABLE utilisateur_fiche_vitrine RENAME INDEX IDX_VITRINE_JEU TO IDX_4D3763EC8C9E392E');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur_fiche_vitrine RENAME INDEX IDX_4D3763ECFB88E14F TO IDX_VITRINE_UTILISATEUR');
        $this->addSql('ALTER TABLE utilisateur_fiche_vitrine RENAME INDEX IDX_4D3763EC8C9E392E TO IDX_VITRINE_JEU');
    }
}
