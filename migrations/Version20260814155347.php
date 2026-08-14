<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814155347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associe les nouvelles notes à leur auteur sans modifier les avis importés.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis ADD auteur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF060BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8F91ABF060BB6FE6 ON avis (auteur_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AVIS_JEU_AUTEUR ON avis (jeu_id, auteur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF060BB6FE6');
        $this->addSql('DROP INDEX IDX_8F91ABF060BB6FE6 ON avis');
        $this->addSql('DROP INDEX UNIQ_AVIS_JEU_AUTEUR ON avis');
        $this->addSql('ALTER TABLE avis DROP auteur_id');
    }
}
