<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815012000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les mentions J’aime aux commentaires de jeux.'; }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commentaire_jeu_aime (commentaire_jeu_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_CJA_COMMENTAIRE (commentaire_jeu_id), INDEX IDX_CJA_UTILISATEUR (utilisateur_id), PRIMARY KEY (commentaire_jeu_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commentaire_jeu_aime ADD CONSTRAINT FK_CJA_COMMENTAIRE FOREIGN KEY (commentaire_jeu_id) REFERENCES commentaire_jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_jeu_aime ADD CONSTRAINT FK_CJA_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }
    public function down(Schema $schema): void { $this->addSql('DROP TABLE commentaire_jeu_aime'); }
}
