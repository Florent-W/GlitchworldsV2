<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913140000 extends AbstractMigration
{
    public function getDescription(): string { return 'Permet aux membres de suivre les mises à jour des jeux.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE utilisateur_jeu_suivi (utilisateur_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_7CF73C5BFB88E14F (utilisateur_id), INDEX IDX_7CF73C5B8C9E392E (jeu_id), PRIMARY KEY (utilisateur_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_jeu_suivi ADD CONSTRAINT FK_SUIVI_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_jeu_suivi ADD CONSTRAINT FK_SUIVI_JEU FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur_jeu_suivi DROP FOREIGN KEY FK_SUIVI_UTILISATEUR');
        $this->addSql('ALTER TABLE utilisateur_jeu_suivi DROP FOREIGN KEY FK_SUIVI_JEU');
        $this->addSql('DROP TABLE utilisateur_jeu_suivi');
    }
}
