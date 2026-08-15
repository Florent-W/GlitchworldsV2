<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815022000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les index des réactions aux publications avec Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_aime RENAME INDEX IDX_PA_PUBLICATION TO IDX_3ABBE2538B217A7');
        $this->addSql('ALTER TABLE publication_aime RENAME INDEX IDX_PA_UTILISATEUR TO IDX_3ABBE25FB88E14F');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE publication_aime RENAME INDEX IDX_3ABBE2538B217A7 TO IDX_PA_PUBLICATION');
        $this->addSql('ALTER TABLE publication_aime RENAME INDEX IDX_3ABBE25FB88E14F TO IDX_PA_UTILISATEUR');
    }
}
