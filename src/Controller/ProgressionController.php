<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\MouvementProgressionRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProgressionController extends AbstractController
{
    #[Route('/classement', name: 'app_classement', methods: ['GET'])]
    public function classement(UtilisateurRepository $utilisateurs): Response
    {
        return $this->render('progression/classement.html.twig', ['membres' => $utilisateurs->trouverClassement()]);
    }

    #[Route('/mon-compte/progression', name: 'app_progression_historique', methods: ['GET'])]
    public function historique(MouvementProgressionRepository $mouvements): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }

        return $this->render('progression/historique.html.twig', [
            'mouvements' => $mouvements->trouverPour($utilisateur, 100),
        ]);
    }
}
