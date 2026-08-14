<?php

namespace App\Controller;

use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Enum\TriJeu;
use App\Form\CommentaireJeuType;
use App\Repository\CategorieJeuRepository;
use App\Repository\AvisRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\GenreRepository;
use App\Repository\JeuRepository;
use App\Repository\LangueRepository;
use App\Repository\PlateformeRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function show(
        Request $request,
        string $slug,
        int $id,
        JeuRepository $jeuRepository,
        AvisRepository $avisRepository,
        CommentaireJeuRepository $commentaireJeuRepository,
        EntityManagerInterface $entityManager,
    ): Response
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

        $commentaire = new CommentaireJeu();
        $formulaireCommentaire = $this->createForm(CommentaireJeuType::class, $commentaire, [
            'disabled' => !$this->getUser() instanceof Utilisateur,
        ]);
        $formulaireCommentaire->handleRequest($request);

        if ($formulaireCommentaire->isSubmitted() && $formulaireCommentaire->isValid()) {
            $auteur = $this->getUser();
            if (!$auteur instanceof Utilisateur) {
                throw $this->createAccessDeniedException('Connecte-toi pour publier un commentaire.');
            }

            $commentaire->setJeu($jeu)->setAuteur($auteur);
            $entityManager->persist($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Ton commentaire a été publié.');

            return $this->redirect($this->generateUrl('app_jeu_show', [
                'slug' => $jeu->getSlug(),
                'id' => $jeu->getId(),
            ]).'#commentaires');
        }

        return $this->render('jeu/show.html.twig', [
            'jeu' => $jeu,
            'jeuxSimilaires' => $jeuRepository->trouverSimilaires($jeu),
            'resumeAvis' => $avisRepository->trouverResume($jeu),
            'commentaires' => $commentaireJeuRepository->trouverRecents($jeu),
            'totalCommentaires' => $commentaireJeuRepository->compterPourJeu($jeu),
            'formulaireCommentaire' => $formulaireCommentaire,
        ]);
    }
}
