<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RechercheController extends AbstractController
{
    #[Route('/recherche', name: 'app_recherche', methods: ['GET'])]
    public function rechercher(Request $request, JeuRepository $jeuRepository, ActualiteRepository $actualiteRepository): Response
    {
        $recherche = trim((string) $request->query->get('recherche', ''));

        return $this->render('recherche/index.html.twig', [
            'recherche' => $recherche,
            'jeux' => '' !== $recherche ? $jeuRepository->rechercherPourApercu($recherche) : [],
            'actualites' => '' !== $recherche ? $actualiteRepository->rechercherPourApercu($recherche) : [],
        ]);
    }
}
