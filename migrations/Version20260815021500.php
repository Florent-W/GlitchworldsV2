<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815021500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les mentions J’aime aux publications communautaires.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE publication_aime (publication_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_PA_PUBLICATION (publication_id), INDEX IDX_PA_UTILISATEUR (utilisateur_id), PRIMARY KEY (publication_id, utilisateur_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE publication_aime ADD CONSTRAINT FK_PA_PUBLICATION FOREIGN KEY (publication_id) REFERENCES publication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication_aime ADD CONSTRAINT FK_PA_UTILISATEUR FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE publication_aime');
    }
}
