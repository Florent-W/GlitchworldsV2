<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Repository\CategorieJeuRepository;
use App\Repository\GenreRepository;
use App\Repository\JeuRepository;
use App\Repository\PlateformeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JeuController extends AbstractController
{
    #[Route('/jeux', name: 'app_jeux')]
    public function liste(
        Request $request,
        JeuRepository $jeuRepository,
        CategorieJeuRepository $categorieJeuRepository,
        PlateformeRepository $plateformeRepository,
        GenreRepository $genreRepository,
    ): Response {
        $page = $request->query->getInt('page', 1);
        $recherche = trim((string) $request->query->get('recherche', ''));
        $categorie = trim((string) $request->query->get('categorie', ''));
        $plateforme = trim((string) $request->query->get('plateforme', ''));
        $genre = trim((string) $request->query->get('genre', ''));
        $pagination = $jeuRepository->trouverApprouvesPagines($page, 20, $recherche, $categorie, $plateforme, $genre);

        return $this->render('jeu/index.html.twig', [
            ...$pagination,
            'categories' => $categorieJeuRepository->trouverToutes(),
            'plateformes' => $plateformeRepository->trouverToutes(),
            'genres' => $genreRepository->trouverTous(),
        ]);
    }

    #[Route('/jeu/{slug}-{id}', name: 'app_jeu_show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, int $id, JeuRepository $jeuRepository): Response
    {
        $jeu = $jeuRepository->find($id);

        if (!$jeu instanceof Jeu) {
            throw $this->createNotFoundException('Ce jeu n\'existe pas.');
        }

        if ($jeu->getSlug() !== $slug) {
            return $this->redirectToRoute('app_jeu_show', [
                'slug' => $jeu->getSlug(),
                'id' => $jeu->getId(),
            ], 301);
        }

        return $this->render('jeu/show.html.twig', [
            'jeu' => $jeu,
        ]);
    }
}
