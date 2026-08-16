<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260817010000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute présence, médias, liens, sondages et réponses communautaires'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reponse_publication (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, publiee_le DATETIME NOT NULL, publication_id INT NOT NULL, auteur_id INT DEFAULT NULL, INDEX IDX_A697527C38B217A7 (publication_id), INDEX IDX_A697527C60BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vote_publication (id INT AUTO_INCREMENT NOT NULL, option_choisie INT NOT NULL, publication_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_1CB1512D38B217A7 (publication_id), INDEX IDX_1CB1512DFB88E14F (utilisateur_id), UNIQUE INDEX UNIQ_VOTE_PUBLICATION_MEMBRE (publication_id, utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reponse_publication ADD CONSTRAINT FK_A697527C38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE, ADD CONSTRAINT FK_A697527C60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE vote_publication ADD CONSTRAINT FK_1CB1512D38B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE, ADD CONSTRAINT FK_1CB1512DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql("ALTER TABLE publication ADD image VARCHAR(255) DEFAULT NULL, ADD lien VARCHAR(500) DEFAULT NULL, ADD question_sondage VARCHAR(180) DEFAULT NULL, ADD options_sondage JSON NOT NULL");
        $this->addSql('ALTER TABLE utilisateur ADD derniere_activite DATETIME DEFAULT NULL');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vote_publication'); $this->addSql('DROP TABLE reponse_publication');
        $this->addSql('ALTER TABLE publication DROP image, DROP lien, DROP question_sondage, DROP options_sondage');
        $this->addSql('ALTER TABLE utilisateur DROP derniere_activite');
    }
}
