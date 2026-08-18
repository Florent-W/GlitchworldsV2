<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260818100000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les vues réelles et le journal de modération'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE vue_page (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT DEFAULT NULL, chemin VARCHAR(180) NOT NULL, type_contenu VARCHAR(20) NOT NULL, contenu_id INT DEFAULT NULL, visiteur_hash VARCHAR(64) NOT NULL, vue_le DATETIME NOT NULL, INDEX IDX_6679173DFB88E14F (utilisateur_id), INDEX vue_page_date_idx (vue_le), INDEX vue_page_contenu_idx (type_contenu, contenu_id, vue_le), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vue_page ADD CONSTRAINT FK_6679173DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('CREATE TABLE action_moderation (id INT AUTO_INCREMENT NOT NULL, moderateur_id INT DEFAULT NULL, action VARCHAR(80) NOT NULL, type_cible VARCHAR(40) NOT NULL, cible_id INT DEFAULT NULL, resume VARCHAR(255) NOT NULL, details JSON NOT NULL, effectuee_le DATETIME NOT NULL, INDEX IDX_A348006E20A01F78 (moderateur_id), INDEX action_moderation_date_idx (effectuee_le), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action_moderation ADD CONSTRAINT FK_A348006E20A01F78 FOREIGN KEY (moderateur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE vue_page'); $this->addSql('DROP TABLE action_moderation'); }
}
