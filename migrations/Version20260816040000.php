<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260816040000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute Mes jeux, listes personnalisées, succès et notifications'; }
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE jeu_bibliotheque (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, jeu_id INT NOT NULL, statut VARCHAR(255) NOT NULL, ajoute_le DATETIME NOT NULL, INDEX IDX_2D83ADD8FB88E14F (utilisateur_id), INDEX IDX_2D83ADD88C9E392E (jeu_id), UNIQUE INDEX UNIQ_BIBLIOTHEQUE_MEMBRE_JEU (utilisateur_id, jeu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE liste_jeux (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, nom VARCHAR(80) NOT NULL, description VARCHAR(255) DEFAULT NULL, cree_le DATETIME NOT NULL, INDEX IDX_160275DEFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE liste_jeux_element (liste_jeux_id INT NOT NULL, jeu_id INT NOT NULL, INDEX IDX_3AA7E7CF5D103183 (liste_jeux_id), INDEX IDX_3AA7E7CF8C9E392E (jeu_id), PRIMARY KEY (liste_jeux_id, jeu_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE succes (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(80) NOT NULL, nom VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, icone VARCHAR(40) NOT NULL, couleur VARCHAR(20) NOT NULL, points INT NOT NULL, UNIQUE INDEX UNIQ_BFC2238377153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE succes_utilisateur (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, succes_id INT NOT NULL, debloque_le DATETIME NOT NULL, INDEX IDX_A011545BFB88E14F (utilisateur_id), INDEX IDX_A011545B4EF1B4AB (succes_id), UNIQUE INDEX UNIQ_SUCCES_MEMBRE (utilisateur_id, succes_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, titre VARCHAR(120) NOT NULL, message VARCHAR(255) NOT NULL, icone VARCHAR(40) NOT NULL, url VARCHAR(255) DEFAULT NULL, lue TINYINT(1) NOT NULL, creee_le DATETIME NOT NULL, INDEX IDX_BF5476CAFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE jeu_bibliotheque ADD CONSTRAINT FK_BIB_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE, ADD CONSTRAINT FK_BIB_JEU FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE liste_jeux ADD CONSTRAINT FK_LISTE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE liste_jeux_element ADD CONSTRAINT FK_LISTE_ELEMENT_LISTE FOREIGN KEY (liste_jeux_id) REFERENCES liste_jeux (id) ON DELETE CASCADE, ADD CONSTRAINT FK_LISTE_ELEMENT_JEU FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE succes_utilisateur ADD CONSTRAINT FK_SUCCES_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE, ADD CONSTRAINT FK_SUCCES_DEFINITION FOREIGN KEY (succes_id) REFERENCES succes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_NOTIFICATION_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql("INSERT INTO succes (code, nom, description, icone, couleur, points) VALUES ('premier_jeu', 'Premier pas', 'Ajoute ton premier jeu à ta bibliothèque.', 'controller', 'primary', 25), ('collectionneur_5', 'Collectionneur', 'Ajoute cinq jeux à ta bibliothèque.', 'collection-fill', 'success', 75), ('niveau_5', 'Habitué de GlitchWorlds', 'Atteins le niveau 5.', 'stars', 'warning', 100)");
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE notification'); $this->addSql('DROP TABLE succes_utilisateur'); $this->addSql('DROP TABLE succes'); $this->addSql('DROP TABLE liste_jeux_element'); $this->addSql('DROP TABLE liste_jeux'); $this->addSql('DROP TABLE jeu_bibliotheque'); }
}
