<?php

namespace App\Service;

use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Entity\Publication;
use App\Entity\Utilisateur;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Prévient les abonnés d'un membre lorsqu'il publie : sans cela, suivre
 * quelqu'un n'aurait aucun effet visible.
 */
final readonly class NotificationAbonnes
{
    public function __construct(
        private CentreNotifications $notifications,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function notifierPublication(Publication $publication): void
    {
        $auteur = $publication->getAuteur();
        if (!$auteur instanceof Utilisateur) {
            return;
        }

        $this->diffuser(
            $auteur,
            'Nouvelle publication',
            sprintf('%s a publié dans la communauté.', $auteur->getPseudo()),
            'chat-quote-fill',
            $this->urlGenerator->generate('app_communaute').'#publication-'.$publication->getId(),
        );
    }

    public function notifierJeu(Jeu $jeu): void
    {
        $createur = $jeu->getCreateur();
        if (!$createur instanceof Utilisateur) {
            return;
        }

        $this->diffuser(
            $createur,
            'Nouveau jeu',
            sprintf('%s a publié la fiche « %s ».', $createur->getPseudo(), $jeu->getNom()),
            'controller',
            $this->urlGenerator->generate('app_jeu_show', [
                'slug' => $jeu->getSlug(),
                'id' => $jeu->getId(),
            ]),
        );
    }

    public function notifierActualite(Actualite $actualite): void
    {
        $auteur = $actualite->getAuteur();
        if (!$auteur instanceof Utilisateur) {
            return;
        }

        $this->diffuser(
            $auteur,
            'Nouvelle actualité',
            sprintf('%s a publié « %s ».', $auteur->getPseudo(), $actualite->getTitre()),
            'newspaper',
            $this->urlGenerator->generate('app_actualite_voir', [
                'slug' => $actualite->getSlug(),
                'id' => $actualite->getId(),
            ]),
        );
    }

    private function diffuser(Utilisateur $auteur, string $titre, string $message, string $icone, string $url): void
    {
        foreach ($auteur->getAbonnes() as $abonne) {
            if ($abonne === $auteur) {
                continue;
            }

            $this->notifications->ajouter($abonne, $titre, $message, $icone, $url);
        }
    }
}
