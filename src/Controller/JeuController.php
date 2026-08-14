<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Enum\StatutJeu;
use App\Enum\TriJeu;
use App\Repository\CategorieJeuRepository;
use App\Repository\GenreRepository;
use App\Repository\JeuRepository;
use App\Repository\LangueRepository;
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
        LangueRepository $langueRepository,
    ): Response {
        $page = $request->query->getInt('page', 1);
        $recherche = trim((string) $request->query->get('recherche', ''));
        $categorie = trim((string) $request->query->get('categorie', ''));
        $plateforme = trim((string) $request->query->get('plateforme', ''));
        $genre = trim((string) $request->query->get('genre', ''));
        $langue = trim((string) $request->query->get('langue', ''));
        $tri = TriJeu::tryFrom((string) $request->query->get('tri', 'recent')) ?? TriJeu::Recent;
        $pagination = $jeuRepository->trouverApprouvesPagines($page, 20, $recherche, $categorie, $plateforme, $genre, $langue, $tri);

        return $this->render('jeu/index.html.twig', [
            ...$pagination,
            'categories' => $categorieJeuRepository->trouverToutes(),
            'plateformes' => $plateformeRepository->trouverToutes(),
            'genres' => $genreRepository->trouverTous(),
            'langues' => $langueRepository->trouverToutes(),
            'tris' => TriJeu::cases(),
        ]);
    }

    #[Route('/jeu/{slug}-{id}', name: 'app_jeu_show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, int $id, JeuRepository $jeuRepository): Response
    {
        $jeu = $jeuRepository->find($id);

        if (!$jeu instanceof Jeu || $jeu->getStatut() !== StatutJeu::Approuve) {
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
            'jeuxSimilaires' => $jeuRepository->trouverSimilaires($jeu),
        ]);
    }
}
