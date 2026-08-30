<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260818140000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute le registre anti-abus XP/points et les badges de niveau'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mouvement_progression (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, categorie VARCHAR(40) NOT NULL, cle_source VARCHAR(191) NOT NULL, libelle VARCHAR(255) NOT NULL, experience INT NOT NULL, points INT NOT NULL, cree_le DATETIME NOT NULL, INDEX IDX_531FC0E1FB88E14F (utilisateur_id), UNIQUE INDEX UNIQ_MOUVEMENT_SOURCE_MEMBRE (utilisateur_id, cle_source), INDEX mouvement_membre_date_idx (utilisateur_id, cree_le), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mouvement_progression ADD CONSTRAINT FK_86E0F627FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql("INSERT IGNORE INTO succes (code, nom, description, icone, couleur, points) VALUES ('niveau_5','Aventurier','Atteindre le niveau 5','award-fill','primary',0),('niveau_10','Explorateur confirmé','Atteindre le niveau 10','shield-fill-check','info',0),('niveau_20','Maître des mondes','Atteindre le niveau 20','gem','warning',0),('niveau_50','Légende GlitchWorlds','Atteindre le niveau 50','stars','danger',0)");
    }
    public function down(Schema $schema): void { $this->addSql("DELETE FROM succes WHERE code IN ('niveau_5','niveau_10','niveau_20','niveau_50')"); $this->addSql('DROP TABLE mouvement_progression'); }
}
