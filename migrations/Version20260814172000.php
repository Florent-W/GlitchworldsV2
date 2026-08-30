<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Aligne les index et le type de date des actualités avec le mapping Doctrine.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite CHANGE publiee_le publiee_le DATETIME NOT NULL');
        $this->addSql('ALTER TABLE actualite RENAME INDEX UNIQ_17DB3F342B36786B TO UNIQ_54928197989D9B62');
        $this->addSql('ALTER TABLE actualite RENAME INDEX IDX_17DB3F3460BB6FE6 TO IDX_5492819760BB6FE6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE actualite CHANGE publiee_le publiee_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql('ALTER TABLE actualite RENAME INDEX UNIQ_54928197989D9B62 TO UNIQ_17DB3F342B36786B');
        $this->addSql('ALTER TABLE actualite RENAME INDEX IDX_5492819760BB6FE6 TO IDX_17DB3F3460BB6FE6');
    }
}
