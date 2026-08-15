<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815024500 extends AbstractMigration
{
    public function getDescription(): string { return 'Aligne les noms de colonne et d’index de la messagerie avec Doctrine.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation CHANGE mise_a_jour_le mise_ajour_le DATETIME NOT NULL');
        $this->addSql('ALTER TABLE conversation RENAME INDEX IDX_CONV_A TO IDX_8A8E26E97BFCEF67');
        $this->addSql('ALTER TABLE conversation RENAME INDEX IDX_CONV_B TO IDX_8A8E26E969494089');
        $this->addSql('ALTER TABLE message RENAME INDEX IDX_MSG_CONVERSATION TO IDX_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message RENAME INDEX IDX_MSG_AUTEUR TO IDX_B6BD307F60BB6FE6');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation CHANGE mise_ajour_le mise_a_jour_le DATETIME NOT NULL');
        $this->addSql('ALTER TABLE conversation RENAME INDEX IDX_8A8E26E97BFCEF67 TO IDX_CONV_A');
        $this->addSql('ALTER TABLE conversation RENAME INDEX IDX_8A8E26E969494089 TO IDX_CONV_B');
        $this->addSql('ALTER TABLE message RENAME INDEX IDX_B6BD307F9AC0396 TO IDX_MSG_CONVERSATION');
        $this->addSql('ALTER TABLE message RENAME INDEX IDX_B6BD307F60BB6FE6 TO IDX_MSG_AUTEUR');
    }
}
