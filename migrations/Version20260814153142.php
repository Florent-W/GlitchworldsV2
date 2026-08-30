<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260814153142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les jeux favoris des utilisateurs.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE utilisateur_jeu_favori (utilisateur_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_6E69FFF7FB88E14F (utilisateur_id), INDEX IDX_6E69FFF78C9E392E (jeu_id), PRIMARY KEY (utilisateur_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_jeu_favori ADD CONSTRAINT FK_6E69FFF7FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_jeu_favori ADD CONSTRAINT FK_6E69FFF78C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur_jeu_favori DROP FOREIGN KEY FK_6E69FFF7FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_jeu_favori DROP FOREIGN KEY FK_6E69FFF78C9E392E');
        $this->addSql('DROP TABLE utilisateur_jeu_favori');
    }
}
