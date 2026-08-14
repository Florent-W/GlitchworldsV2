<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814041059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE jeu_langue (jeu_id INT NOT NULL, langue_id INT NOT NULL, INDEX IDX_2003EAA38C9E392E (jeu_id), INDEX IDX_2003EAA32AADBACD (langue_id), PRIMARY KEY (jeu_id, langue_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE langue (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL, image VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_9357758E989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE jeu_langue ADD CONSTRAINT FK_2003EAA38C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_langue ADD CONSTRAINT FK_2003EAA32AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu ADD CONSTRAINT FK_82E48DB5BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_jeu (id)');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE598C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE59391E226B FOREIGN KEY (plateforme_id) REFERENCES plateforme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_genre ADD CONSTRAINT FK_B1B530008C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_genre ADD CONSTRAINT FK_B1B530004296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE jeu_langue DROP FOREIGN KEY FK_2003EAA38C9E392E');
        $this->addSql('ALTER TABLE jeu_langue DROP FOREIGN KEY FK_2003EAA32AADBACD');
        $this->addSql('DROP TABLE jeu_langue');
        $this->addSql('DROP TABLE langue');
        $this->addSql('ALTER TABLE jeu DROP FOREIGN KEY FK_82E48DB5BCF5E72D');
        $this->addSql('ALTER TABLE jeu_genre DROP FOREIGN KEY FK_B1B530008C9E392E');
        $this->addSql('ALTER TABLE jeu_genre DROP FOREIGN KEY FK_B1B530004296D31F');
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE598C9E392E');
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE59391E226B');
    }
}
