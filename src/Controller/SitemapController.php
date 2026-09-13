<?php

namespace App\Controller;

use App\Repository\JeuRepository;
use App\Repository\ActualiteRepository;
use App\Repository\ListeJeuxRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function index(JeuRepository $jeuRepository, ActualiteRepository $actualiteRepository, ListeJeuxRepository $listeJeuxRepository): Response
    {
        $response = $this->render('sitemap.xml.twig', [
            'jeux' => $jeuRepository->trouverPourSitemap(),
            'actualites' => $actualiteRepository->trouverPourSitemap(),
            'listesPubliques' => $listeJeuxRepository->trouverPubliquesPourSitemap(),
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'])]
    public function robots(): Response
    {
        $response = $this->render('robots.txt.twig');
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $response;
    }
}
