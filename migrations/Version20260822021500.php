<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822021500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute des succès (collection, listes, profil, actualité, boutique, messages).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO succes (code, nom, description, icone, couleur, points) VALUES
            ('collectionneur_50', 'Salle des archives', 'Ajoute cinquante jeux à ta bibliothèque.', 'archive-fill', 'info', 250),
            ('fan_25', 'Cœur à prendre', 'Ajoute vingt-cinq jeux à tes favoris.', 'heart-pulse-fill', 'danger', 150),
            ('critique_15', 'Plume affûtée', 'Note quinze jeux différents.', 'star-half', 'warning', 120),
            ('bavard_50', 'Toujours en ligne', 'Publie cinquante commentaires.', 'chat-heart-fill', 'primary', 200),
            ('chroniqueur_25', 'Chroniqueur', 'Publie vingt-cinq messages dans la communauté.', 'newspaper', 'success', 250),
            ('social_10', 'Carnet d’adresses', 'Suis dix membres.', 'people', 'info', 80),
            ('createur_5', 'Studio maison', 'Fais approuver cinq de tes jeux.', 'joystick', 'success', 400),
            ('premiere_liste', 'Mise en rayon', 'Crée ta première liste de jeux.', 'list-ul', 'primary', 20),
            ('curateur_5', 'Curateur', 'Crée cinq listes de jeux.', 'folders', 'primary', 80),
            ('portrait', 'Portrait', 'Ajoute une photo de profil.', 'person-bounding-box', 'info', 15),
            ('presentation', 'Carte de visite', 'Rédige ta biographie.', 'card-text', 'info', 15),
            ('premiere_banniere', 'Devanture', 'Ajoute une bannière à ton profil.', 'image-fill', 'info', 15),
            ('premiere_actualite', 'À la une', 'Fais publier ta première actualité.', 'lightning-fill', 'warning', 200),
            ('premier_achat', 'Premier échange', 'Achète un article à la boutique.', 'bag-fill', 'success', 30),
            ('premier_message', 'Facteur', 'Envoie ton premier message privé.', 'envelope-fill', 'primary', 20)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM succes WHERE code IN (
            'collectionneur_50', 'fan_25', 'critique_15', 'bavard_50', 'chroniqueur_25',
            'social_10', 'createur_5', 'premiere_liste', 'curateur_5', 'portrait',
            'presentation', 'premiere_banniere', 'premiere_actualite', 'premier_achat',
            'premier_message'
        )");
    }
}
