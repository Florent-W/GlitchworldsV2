<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815002500 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne les commentaires d’actualités avec le mapping Doctrine.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_actualite CHANGE date_commentaire date_commentaire DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commentaire_actualite RENAME INDEX IDX_CA_ACTUALITE TO IDX_DB050EA0A2843073');
        $this->addSql('ALTER TABLE commentaire_actualite RENAME INDEX IDX_CA_AUTEUR TO IDX_DB050EA060BB6FE6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE commentaire_actualite CHANGE date_commentaire date_commentaire DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE commentaire_actualite RENAME INDEX IDX_DB050EA0A2843073 TO IDX_CA_ACTUALITE');
        $this->addSql('ALTER TABLE commentaire_actualite RENAME INDEX IDX_DB050EA060BB6FE6 TO IDX_CA_AUTEUR');
    }
}
