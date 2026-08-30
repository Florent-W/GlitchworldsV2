<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815025500 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les états de lecture, l’archivage et les pièces jointes de la messagerie.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation ADD archivee_par_a TINYINT(1) NOT NULL, ADD archivee_par_b TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE message ADD lu_le DATETIME DEFAULT NULL, ADD piece_jointe VARCHAR(255) DEFAULT NULL');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conversation DROP archivee_par_a, DROP archivee_par_b');
        $this->addSql('ALTER TABLE message DROP lu_le, DROP piece_jointe');
    }
}
