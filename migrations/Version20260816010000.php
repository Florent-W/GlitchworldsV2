<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816010000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les signalements de contenus et leur suivi de modération'; }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE signalement (id INT AUTO_INCREMENT NOT NULL, signale_par_id INT DEFAULT NULL, traite_par_id INT DEFAULT NULL, jeu_id INT DEFAULT NULL, commentaire_jeu_id INT DEFAULT NULL, commentaire_actualite_id INT DEFAULT NULL, publication_id INT DEFAULT NULL, motif VARCHAR(255) NOT NULL, details LONGTEXT DEFAULT NULL, statut VARCHAR(255) NOT NULL, signale_le DATETIME NOT NULL, traite_le DATETIME DEFAULT NULL, INDEX IDX_F4B55114AE190A20 (signale_par_id), INDEX IDX_F4B55114167FABE8 (traite_par_id), INDEX IDX_F4B551148C9E392E (jeu_id), INDEX IDX_F4B55114981AE9A9 (commentaire_jeu_id), INDEX IDX_F4B5511451D0418A (commentaire_actualite_id), INDEX IDX_F4B5511438B217A7 (publication_id), INDEX signalement_moderation_idx (statut, signale_le), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_46CE75C86B77DA60 FOREIGN KEY (signale_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_46CE75C8A8FAFA5 FOREIGN KEY (traite_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_46CE75C8A80A88E5 FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_46CE75C853361AFB FOREIGN KEY (commentaire_jeu_id) REFERENCES commentaire_jeu (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_46CE75C89535DB32 FOREIGN KEY (commentaire_actualite_id) REFERENCES commentaire_actualite (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE signalement ADD CONSTRAINT FK_46CE75C838B217A7 FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void { $this->addSql('DROP TABLE signalement'); }
}
