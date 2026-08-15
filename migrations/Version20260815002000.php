<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815002000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les commentaires des actualités.'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE commentaire_actualite (id INT AUTO_INCREMENT NOT NULL, actualite_id INT NOT NULL, auteur_id INT DEFAULT NULL, contenu LONGTEXT NOT NULL, date_commentaire DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_CA_ACTUALITE (actualite_id), INDEX IDX_CA_AUTEUR (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB");
        $this->addSql('ALTER TABLE commentaire_actualite ADD CONSTRAINT FK_CA_ACTUALITE FOREIGN KEY (actualite_id) REFERENCES actualite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_actualite ADD CONSTRAINT FK_CA_AUTEUR FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void { $this->addSql('DROP TABLE commentaire_actualite'); }
}
