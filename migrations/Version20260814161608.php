<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814161608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Associe les nouvelles propositions de jeux à leur créateur.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jeu ADD createur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE jeu ADD CONSTRAINT FK_82E48DB573A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_82E48DB573A201E5 ON jeu (createur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jeu DROP FOREIGN KEY FK_82E48DB573A201E5');
        $this->addSql('DROP INDEX IDX_82E48DB573A201E5 ON jeu');
        $this->addSql('ALTER TABLE jeu DROP createur_id');
    }
}
