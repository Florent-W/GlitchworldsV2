<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815024000 extends AbstractMigration
{
    public function getDescription(): string { return 'Ajoute les conversations et messages privés.'; }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, membre_a_id INT NOT NULL, membre_b_id INT NOT NULL, mise_a_jour_le DATETIME NOT NULL, INDEX IDX_CONV_A (membre_a_id), INDEX IDX_CONV_B (membre_b_id), UNIQUE INDEX conversation_membres_unique (membre_a_id, membre_b_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, auteur_id INT DEFAULT NULL, contenu LONGTEXT NOT NULL, envoye_le DATETIME NOT NULL, INDEX IDX_MSG_CONVERSATION (conversation_id), INDEX IDX_MSG_AUTEUR (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_CONV_A FOREIGN KEY (membre_a_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_CONV_B FOREIGN KEY (membre_b_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_MSG_CONVERSATION FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_MSG_AUTEUR FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE conversation');
    }
}
