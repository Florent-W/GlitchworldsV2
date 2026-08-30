<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815010000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les mentions J’aime aux commentaires d’actualités.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commentaire_actualite_aime (commentaire_actualite_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_CAA_COMMENTAIRE (commentaire_actualite_id), INDEX IDX_CAA_UTILISATEUR (utilisateur_id), PRIMARY KEY (commentaire_actualite_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commentaire_actualite_aime ADD CONSTRAINT FK_CAA_COMMENTAIRE FOREIGN KEY (commentaire_actualite_id) REFERENCES commentaire_actualite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_actualite_aime ADD CONSTRAINT FK_CAA_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void { $this->addSql('DROP TABLE commentaire_actualite_aime'); }
}
