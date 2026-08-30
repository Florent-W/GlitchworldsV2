<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260817020000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute le rattachement sécurisé des comptes Google, Discord et GitHub'; }
    public function up(Schema $schema): void { $this->addSql('CREATE TABLE identite_oauth (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, fournisseur VARCHAR(20) NOT NULL, identifiant VARCHAR(191) NOT NULL, email VARCHAR(180) DEFAULT NULL, liee_le DATETIME NOT NULL, INDEX IDX_4EFFC48EFB88E14F (utilisateur_id), UNIQUE INDEX UNIQ_OAUTH_FOURNISSEUR_IDENTIFIANT (fournisseur, identifiant), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'); $this->addSql('ALTER TABLE identite_oauth ADD CONSTRAINT FK_4EFFC48EFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE'); }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE identite_oauth'); }
}
