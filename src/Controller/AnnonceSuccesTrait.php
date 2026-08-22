<?php

namespace App\Controller;

use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use App\Service\GestionSucces;

/** Annonce les succès venant d’être débloqués via des flash messages. */
trait AnnonceSuccesTrait
{
    /** Paliers de rareté, identiques à ceux des récompenses. */
    private const PALIERS_SUCCES = ['commun', 'rare', 'epique', 'mythique', 'legendaire'];

    /** @param list<SuccesUtilisateur> $debloques */
    private function annoncerSucces(array $debloques): void
    {
        foreach ($debloques as $deblocage) {
            $succes = $deblocage->getSucces();
            if ($succes === null) {
                continue;
            }

            $palier = $succes->getPalier();

            $this->addFlash('succes_debloque', [
                'nom' => $succes->getNom(),
                'description' => $succes->getDescription(),
                'icone' => preg_match('/^[a-z0-9-]{1,40}$/', $succes->getIcone()) === 1
                    ? $succes->getIcone()
                    : 'trophy-fill',
                'couleur' => in_array($palier, self::PALIERS_SUCCES, true) ? $palier : 'epique',
                'points' => $succes->getPoints(),
            ]);
        }
    }

    private function verifierEtAnnoncerSucces(Utilisateur $utilisateur, GestionSucces $gestionSucces): void
    {
        $this->annoncerSucces($gestionSucces->verifier($utilisateur));
    }
}
