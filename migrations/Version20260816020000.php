<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816020000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute la boutique, les achats et les titres de profil'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE article_boutique (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, prix INT NOT NULL, type VARCHAR(255) NOT NULL, icone VARCHAR(60) NOT NULL, couleur VARCHAR(20) NOT NULL, actif TINYINT(1) DEFAULT 1 NOT NULL, stock INT DEFAULT NULL, UNIQUE INDEX UNIQ_F4CAC83F989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE achat_boutique (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, article_id INT NOT NULL, prix_paye INT NOT NULL, achete_le DATETIME NOT NULL, INDEX IDX_5A1DBA7EFB88E14F (utilisateur_id), INDEX IDX_5A1DBA7E7294869C (article_id), UNIQUE INDEX UNIQ_ACHAT_BOUTIQUE_MEMBRE_ARTICLE (utilisateur_id, article_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE achat_boutique ADD CONSTRAINT FK_ACHAT_BOUTIQUE_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE achat_boutique ADD CONSTRAINT FK_ACHAT_BOUTIQUE_ARTICLE FOREIGN KEY (article_id) REFERENCES article_boutique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur ADD titre_equipe_id INT DEFAULT NULL, ADD INDEX IDX_1D1C63B329572DA0 (titre_equipe_id)');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_UTILISATEUR_TITRE_EQUIPE FOREIGN KEY (titre_equipe_id) REFERENCES article_boutique (id) ON DELETE SET NULL');
        $this->addSql("INSERT INTO article_boutique (nom, slug, description, prix, type, icone, couleur, actif, stock) VALUES
            ('Explorateur du glitch', 'explorateur-du-glitch', 'Un titre pour celles et ceux qui découvrent tous les mondes.', 100, 'titre', 'compass-fill', 'primary', 1, NULL),
            ('Chasseur de bugs', 'chasseur-de-bugs', 'Affiche ton goût pour les secrets et les anomalies.', 250, 'titre', 'bug-fill', 'danger', 1, NULL),
            ('Créateur passionné', 'createur-passionne', 'Un titre dédié aux créateurs de fangames.', 500, 'titre', 'controller', 'success', 1, NULL),
            ('Étoile GlitchWorlds', 'etoile-glitchworlds', 'Un badge lumineux visible sur ton profil.', 150, 'badge', 'star-fill', 'warning', 1, NULL),
            ('Pixel légendaire', 'pixel-legendaire', 'Un badge rare pour les membres les plus actifs.', 750, 'badge', 'gem', 'info', 1, 100)");
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_UTILISATEUR_TITRE_EQUIPE');
        $this->addSql('ALTER TABLE utilisateur DROP titre_equipe_id');
        $this->addSql('DROP TABLE achat_boutique');
        $this->addSql('DROP TABLE article_boutique');
    }
}
