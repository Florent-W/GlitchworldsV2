<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute de nouveaux succès communautaires (favoris, notes, commentaires, publications, suivi, création).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO succes (code, nom, description, icone, couleur, points) VALUES
            ('collectionneur_20', 'Archiviste', 'Ajoute vingt jeux à ta bibliothèque.', 'journal-bookmark-fill', 'info', 150),
            ('premier_favori', 'Coup de cœur', 'Ajoute ton premier jeu aux favoris.', 'heart-fill', 'danger', 15),
            ('fan_10', 'Super fan', 'Ajoute dix jeux à tes favoris.', 'balloon-heart-fill', 'danger', 80),
            ('premiere_note', 'Critique en herbe', 'Donne ta première note à un jeu.', 'star-fill', 'warning', 20),
            ('critique_5', 'Critique affirmé', 'Note cinq jeux différents.', 'stars', 'warning', 60),
            ('premier_commentaire', 'Prise de parole', 'Publie ton premier commentaire.', 'chat-dots-fill', 'primary', 20),
            ('bavard_25', 'Habitué du fil', 'Publie vingt-cinq commentaires.', 'chat-quote-fill', 'primary', 120),
            ('premiere_publication', 'Dans le salon', 'Publie ton premier message communautaire.', 'megaphone-fill', 'success', 30),
            ('voix_de_la_communaute', 'Voix de la communauté', 'Publie dix messages dans la communauté.', 'people-fill', 'success', 150),
            ('premier_suivi', 'Curieux', 'Suis ton premier membre.', 'person-plus-fill', 'info', 15),
            ('createur_approuve', 'Créateur reconnu', 'Propose un jeu qui est approuvé.', 'patch-check-fill', 'success', 200)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM succes WHERE code IN (
            'collectionneur_20', 'premier_favori', 'fan_10', 'premiere_note', 'critique_5',
            'premier_commentaire', 'bavard_25', 'premiere_publication', 'voix_de_la_communaute',
            'premier_suivi', 'createur_approuve'
        )");
    }
}
