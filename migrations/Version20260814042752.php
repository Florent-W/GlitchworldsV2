<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814042752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, note DOUBLE PRECISION NOT NULL, date_avis DATETIME NOT NULL, jeu_id INT NOT NULL, INDEX IDX_8F91ABF08C9E392E (jeu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF08C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu ADD CONSTRAINT FK_82E48DB5BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_jeu (id)');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE598C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE59391E226B FOREIGN KEY (plateforme_id) REFERENCES plateforme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_genre ADD CONSTRAINT FK_B1B530008C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_genre ADD CONSTRAINT FK_B1B530004296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_langue ADD CONSTRAINT FK_2003EAA38C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_langue ADD CONSTRAINT FK_2003EAA32AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF08C9E392E');
        $this->addSql('DROP TABLE avis');
        $this->addSql('ALTER TABLE jeu DROP FOREIGN KEY FK_82E48DB5BCF5E72D');
        $this->addSql('ALTER TABLE jeu_genre DROP FOREIGN KEY FK_B1B530008C9E392E');
        $this->addSql('ALTER TABLE jeu_genre DROP FOREIGN KEY FK_B1B530004296D31F');
        $this->addSql('ALTER TABLE jeu_langue DROP FOREIGN KEY FK_2003EAA38C9E392E');
        $this->addSql('ALTER TABLE jeu_langue DROP FOREIGN KEY FK_2003EAA32AADBACD');
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE598C9E392E');
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE59391E226B');
    }
}
