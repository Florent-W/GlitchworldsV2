<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815004500 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne les index de la relation jeux–actualités.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite_jeu RENAME INDEX IDX_AJ_ACTUALITE TO IDX_209E4190A2843073');
        $this->addSql('ALTER TABLE actualite_jeu RENAME INDEX IDX_AJ_JEU TO IDX_209E41908C9E392E');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite_jeu RENAME INDEX IDX_209E4190A2843073 TO IDX_AJ_ACTUALITE');
        $this->addSql('ALTER TABLE actualite_jeu RENAME INDEX IDX_209E41908C9E392E TO IDX_AJ_JEU');
    }
}
