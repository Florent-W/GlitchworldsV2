<?php

namespace App\Service;

use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class NotificationPropositionModeration
{
    public function __construct(
        private CentreNotifications $notifications,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function notifierJeu(Jeu $jeu, bool $approuve): void
    {
        $createur = $jeu->getCreateur();
        if (!$createur instanceof Utilisateur) {
            return;
        }

        if ($approuve) {
            $this->notifications->ajouter(
                $createur,
                'Jeu approuvé',
                sprintf('Ta proposition « %s » est maintenant visible sur Glitchworlds.', $jeu->getNom()),
                'controller',
                $this->urlGenerator->generate('app_jeu_show', [
                    'slug' => $jeu->getSlug(),
                    'id' => $jeu->getId(),
                ]),
            );

            return;
        }

        $this->notifications->ajouter(
            $createur,
            'Jeu refusé',
            sprintf('Ta proposition « %s » n’a pas été retenue par la modération.', $jeu->getNom()),
            'x-circle-fill',
            $this->urlGenerator->generate('app_compte'),
        );
    }

    public function notifierActualite(Actualite $actualite, bool $approuve): void
    {
        $auteur = $actualite->getAuteur();
        if (!$auteur instanceof Utilisateur) {
            return;
        }

        if ($approuve) {
            $this->notifications->ajouter(
                $auteur,
                'Actualité publiée',
                sprintf('Ton actualité « %s » a été publiée sur Glitchworlds.', $actualite->getTitre()),
                'newspaper',
                $this->urlGenerator->generate('app_actualite_voir', [
                    'slug' => $actualite->getSlug(),
                    'id' => $actualite->getId(),
                ]),
            );

            return;
        }

        $this->notifications->ajouter(
            $auteur,
            'Actualité refusée',
            sprintf('Ton actualité « %s » n’a pas été retenue par la modération.', $actualite->getTitre()),
            'x-circle-fill',
            $this->urlGenerator->generate('app_compte'),
        );
    }
}
