<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815033000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les réponses aux commentaires des jeux et des actualités';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_actualite ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire_actualite ADD CONSTRAINT FK_DB050EA0727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaire_actualite (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_DB050EA0727ACA70 ON commentaire_actualite (parent_id)');
        $this->addSql('ALTER TABLE commentaire_jeu ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire_jeu ADD CONSTRAINT FK_1985EAB3727ACA70 FOREIGN KEY (parent_id) REFERENCES commentaire_jeu (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_1985EAB3727ACA70 ON commentaire_jeu (parent_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaire_actualite DROP FOREIGN KEY FK_DB050EA0727ACA70');
        $this->addSql('DROP INDEX IDX_DB050EA0727ACA70 ON commentaire_actualite');
        $this->addSql('ALTER TABLE commentaire_actualite DROP parent_id');
        $this->addSql('ALTER TABLE commentaire_jeu DROP FOREIGN KEY FK_1985EAB3727ACA70');
        $this->addSql('DROP INDEX IDX_1985EAB3727ACA70 ON commentaire_jeu');
        $this->addSql('ALTER TABLE commentaire_jeu DROP parent_id');
    }
}
