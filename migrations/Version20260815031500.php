<?php

declare(strict_types=1);
namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815031500 extends AbstractMigration
{
    public function getDescription(): string { return 'Garantit les clés étrangères des abonnements sur les bases déjà migrées'; }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur_abonnement ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_abonnement ADD CONSTRAINT FK_7AAAAADC325A696 FOREIGN KEY (abonne_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_abonnement ADD CONSTRAINT FK_7AAAAAD7FEA59C0 FOREIGN KEY (suivi_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur_abonnement DROP FOREIGN KEY FK_7AAAAADC325A696, DROP FOREIGN KEY FK_7AAAAAD7FEA59C0');
    }
}
