<?php

namespace App\Controller;

use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use App\Service\GestionSucces;

/** Annonce les succès venant d’être débloqués via des flash messages. */
trait AnnonceSuccesTrait
{
    /** @param list<SuccesUtilisateur> $debloques */
    private function annoncerSucces(array $debloques): void
    {
        foreach ($debloques as $deblocage) {
            $succes = $deblocage->getSucces();
            if ($succes === null) {
                continue;
            }
            $this->addFlash(
                'success',
                'Succès débloqué : '.$succes->getNom().' (+'.$succes->getPoints().' pts)'
            );
        }
    }

    private function verifierEtAnnoncerSucces(Utilisateur $utilisateur, GestionSucces $gestionSucces): void
    {
        $this->annoncerSucces($gestionSucces->verifier($utilisateur));
    }
}
