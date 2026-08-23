<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le blocage entre membres et les signalements de profils, avis et messages privés.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE utilisateur_blocage (bloqueur_id INT NOT NULL, bloque_id INT NOT NULL, INDEX IDX_BLOCAGE_BLOQUEUR (bloqueur_id), INDEX IDX_BLOCAGE_BLOQUE (bloque_id), PRIMARY KEY(bloqueur_id, bloque_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE utilisateur_blocage ADD CONSTRAINT FK_BLOCAGE_BLOQUEUR FOREIGN KEY (bloqueur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_blocage ADD CONSTRAINT FK_BLOCAGE_BLOQUE FOREIGN KEY (bloque_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE signalement ADD profil_id INT DEFAULT NULL, ADD avis_id INT DEFAULT NULL, ADD message_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_SIGNALEMENT_PROFIL FOREIGN KEY (profil_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_SIGNALEMENT_AVIS FOREIGN KEY (avis_id) REFERENCES avis (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_SIGNALEMENT_MESSAGE FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_SIGNALEMENT_PROFIL ON signalement (profil_id)');
        $this->addSql('CREATE INDEX IDX_SIGNALEMENT_AVIS ON signalement (avis_id)');
        $this->addSql('CREATE INDEX IDX_SIGNALEMENT_MESSAGE ON signalement (message_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur_blocage DROP FOREIGN KEY FK_BLOCAGE_BLOQUEUR');
        $this->addSql('ALTER TABLE utilisateur_blocage DROP FOREIGN KEY FK_BLOCAGE_BLOQUE');
        $this->addSql('DROP TABLE utilisateur_blocage');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_SIGNALEMENT_PROFIL');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_SIGNALEMENT_AVIS');
        $this->addSql('ALTER TABLE signalement DROP FOREIGN KEY FK_SIGNALEMENT_MESSAGE');
        $this->addSql('DROP INDEX IDX_SIGNALEMENT_PROFIL ON signalement');
        $this->addSql('DROP INDEX IDX_SIGNALEMENT_AVIS ON signalement');
        $this->addSql('DROP INDEX IDX_SIGNALEMENT_MESSAGE ON signalement');
        $this->addSql('ALTER TABLE signalement DROP profil_id, DROP avis_id, DROP message_id');
    }
}
