<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Entity\CommentaireActualite;
use App\Entity\Utilisateur;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use App\Repository\ActualiteRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Form\CommentaireActualiteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ProgressionUtilisateur;

final class ActualiteController extends AbstractController
{
    #[Route('/actualites', name: 'app_actualites', methods: ['GET'])]
    public function liste(Request $request, ActualiteRepository $actualiteRepository): Response
    {
        $categorie = CategorieActualite::tryFrom((string) $request->query->get('categorie'));
        $recherche = trim((string) $request->query->get('recherche', ''));

        return $this->render('actualite/index.html.twig', [
            ...$actualiteRepository->trouverPubliees($request->query->getInt('page', 1), 12, $categorie, $recherche),
            'categorieSelectionnee' => $categorie,
            'categories' => CategorieActualite::cases(),
            'recherche' => $recherche,
        ]);
    }

    #[Route('/actualite/{slug}-{id}', name: 'app_actualite_voir', requirements: ['slug' => '[a-z0-9\-]+', 'id' => '\d+'], methods: ['GET'])]
    public function voir(string $slug, Actualite $actualite, Request $request, CommentaireActualiteRepository $commentaireRepository, EntityManagerInterface $entityManager, ProgressionUtilisateur $progression): Response
    {
        if ($actualite->getStatut() !== StatutActualite::Publiee) {
            throw $this->createNotFoundException('Cette actualité n’existe pas.');
        }

        if ($actualite->getSlug() !== $slug) {
            return $this->redirectToRoute('app_actualite_voir', [
                'slug' => $actualite->getSlug(),
                'id' => $actualite->getId(),
            ], Response::HTTP_MOVED_PERMANENTLY);
        }

        $commentaire = new CommentaireActualite();
        $formulaire = $this->createForm(CommentaireActualiteType::class, $commentaire, [
            'disabled' => !$this->getUser() instanceof Utilisateur,
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $auteur = $this->getUser();
            if (!$auteur instanceof Utilisateur) {
                throw $this->createAccessDeniedException();
            }
            $commentaire->setActualite($actualite)->setAuteur($auteur);
            $entityManager->persist($commentaire);
            $recompense = $progression->recompenseCommentaire($auteur, 'actualite:'.$actualite->getId().':'.hash('sha256', mb_strtolower(trim($commentaire->getContenu()))));
            $entityManager->flush();
            $this->addFlash('success', 'Ton commentaire a été publié.'.($recompense ? ' +10 XP et +5 points.' : ''));

            return $this->redirect($this->generateUrl('app_actualite_voir', [
                'slug' => $actualite->getSlug(),
                'id' => $actualite->getId(),
            ]).'#commentaires');
        }

        return $this->render('actualite/voir.html.twig', [
            'actualite' => $actualite,
            'commentaires' => $commentaireRepository->trouverRecents($actualite),
            'totalCommentaires' => $commentaireRepository->count(['actualite' => $actualite]),
            'formulaireCommentaire' => $formulaire,
        ]);
    }
}
