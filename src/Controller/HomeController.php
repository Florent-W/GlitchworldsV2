<?php

namespace App\Controller;

use App\Enum\StatutJeu;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function accueil(
        JeuRepository $jeuRepository,
        UtilisateurRepository $utilisateurRepository,
        CommentaireJeuRepository $commentaireJeuRepository,
    ): Response
    {
        return $this->render('home/index.html.twig', [
            'nouveautes' => $jeuRepository->trouverNouveautes(),
            'populaires' => $jeuRepository->trouverPopulaires(),
            'totalJeux' => $jeuRepository->count(['statut' => StatutJeu::Approuve]),
            'totalMembres' => $utilisateurRepository->count([]),
            'totalCommentaires' => $commentaireJeuRepository->count([]),
        ]);
    }
}
