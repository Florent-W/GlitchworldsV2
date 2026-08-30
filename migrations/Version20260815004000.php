<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815004000 extends AbstractMigration
{
    public function getDescription(): string { return 'Lie les actualités aux jeux concernés.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE actualite_jeu (actualite_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_AJ_ACTUALITE (actualite_id), INDEX IDX_AJ_JEU (jeu_id), PRIMARY KEY (actualite_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE actualite_jeu ADD CONSTRAINT FK_AJ_ACTUALITE FOREIGN KEY (actualite_id) REFERENCES actualite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE actualite_jeu ADD CONSTRAINT FK_AJ_JEU FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void { $this->addSql('DROP TABLE actualite_jeu'); }
}
