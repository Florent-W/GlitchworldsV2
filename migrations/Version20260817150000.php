<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260817150000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute la mise en avant administrable des actualités sur l’accueil'; }
    public function up(Schema $schema): void { $this->addSql('ALTER TABLE actualite ADD mise_en_avant TINYINT(1) DEFAULT 0 NOT NULL'); }
    public function down(Schema $schema): void { $this->addSql('ALTER TABLE actualite DROP mise_en_avant'); }
}
