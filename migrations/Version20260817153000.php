<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260817153000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les jetons sécurisés de réinitialisation des mots de passe'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE utilisateur ADD jeton_reinitialisation VARCHAR(64) DEFAULT NULL, ADD expiration_jeton_reinitialisation DATETIME DEFAULT NULL, ADD UNIQUE INDEX UNIQ_1D1C63B3711FCE5D (jeton_reinitialisation)'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE utilisateur DROP INDEX UNIQ_1D1C63B3711FCE5D, DROP jeton_reinitialisation, DROP expiration_jeton_reinitialisation'); }
}
