<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260813185749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE jeu_plateforme (jeu_id INT NOT NULL, plateforme_id INT NOT NULL, INDEX IDX_14AAFE598C9E392E (jeu_id), INDEX IDX_14AAFE59391E226B (plateforme_id), PRIMARY KEY (jeu_id, plateforme_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plateforme (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, image VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_3C020C11989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE598C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE59391E226B FOREIGN KEY (plateforme_id) REFERENCES plateforme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu ADD CONSTRAINT FK_82E48DB5BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_jeu (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE598C9E392E');
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE59391E226B');
        $this->addSql('DROP TABLE jeu_plateforme');
        $this->addSql('DROP TABLE plateforme');
        $this->addSql('ALTER TABLE jeu DROP FOREIGN KEY FK_82E48DB5BCF5E72D');
    }
}
