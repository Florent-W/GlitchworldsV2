<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit les tables métier en InnoDB et active les clés étrangères Doctrine.';
    }

    public function up(Schema $schema): void
    {
        foreach (['avis', 'categorie_jeu', 'commentaire_jeu', 'genre', 'jeu', 'jeu_genre', 'jeu_langue', 'jeu_plateforme', 'langue', 'plateforme', 'utilisateur'] as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ENGINE = InnoDB', $table));
        }

        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF08C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_jeu ADD CONSTRAINT FK_1985EAB38C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire_jeu ADD CONSTRAINT FK_1985EAB360BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE jeu ADD CONSTRAINT FK_82E48DB5BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie_jeu (id)');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE598C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_plateforme ADD CONSTRAINT FK_14AAFE59391E226B FOREIGN KEY (plateforme_id) REFERENCES plateforme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_genre ADD CONSTRAINT FK_B1B530008C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_genre ADD CONSTRAINT FK_B1B530004296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_langue ADD CONSTRAINT FK_2003EAA38C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE jeu_langue ADD CONSTRAINT FK_2003EAA32AADBACD FOREIGN KEY (langue_id) REFERENCES langue (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF08C9E392E');
        $this->addSql('ALTER TABLE commentaire_jeu DROP FOREIGN KEY FK_1985EAB38C9E392E, DROP FOREIGN KEY FK_1985EAB360BB6FE6');
        $this->addSql('ALTER TABLE jeu DROP FOREIGN KEY FK_82E48DB5BCF5E72D');
        $this->addSql('ALTER TABLE jeu_plateforme DROP FOREIGN KEY FK_14AAFE598C9E392E, DROP FOREIGN KEY FK_14AAFE59391E226B');
        $this->addSql('ALTER TABLE jeu_genre DROP FOREIGN KEY FK_B1B530008C9E392E, DROP FOREIGN KEY FK_B1B530004296D31F');
        $this->addSql('ALTER TABLE jeu_langue DROP FOREIGN KEY FK_2003EAA38C9E392E, DROP FOREIGN KEY FK_2003EAA32AADBACD');
    }
}
