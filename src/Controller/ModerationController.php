<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Enum\StatutJeu;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderation', name: 'app_moderation_')]
final class ModerationController extends AbstractController
{
    #[Route('/commentaires', name: 'commentaires', methods: ['GET'])]
    public function commentaires(
        CommentaireJeuRepository $commentaireJeuRepository,
        CommentaireActualiteRepository $commentaireActualiteRepository,
    ): Response {
        return $this->render('moderation/commentaires.html.twig', [
            'commentairesJeux' => $commentaireJeuRepository->trouverPourModeration(),
            'commentairesActualites' => $commentaireActualiteRepository->trouverPourModeration(),
        ]);
    }

    #[Route('/jeux', name: 'jeux', methods: ['GET'])]
    public function jeux(JeuRepository $jeuRepository): Response
    {
        return $this->render('moderation/jeux.html.twig', [
            'jeux' => $jeuRepository->trouverEnAttente(),
        ]);
    }

    #[Route('/jeux/{id}', name: 'jeu_voir', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function voir(Jeu $jeu): Response
    {
        if ($jeu->getStatut() !== StatutJeu::EnAttente) {
            throw $this->createNotFoundException('Cette proposition n’est plus en attente.');
        }

        return $this->render('moderation/voir_jeu.html.twig', ['jeu' => $jeu]);
    }

    #[Route('/jeux/{id}/{decision}', name: 'jeu_decider', requirements: ['decision' => 'approuver|refuser'], methods: ['POST'])]
    public function decider(
        Jeu $jeu,
        string $decision,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if ($jeu->getStatut() !== StatutJeu::EnAttente) {
            throw $this->createNotFoundException('Cette proposition n’est plus en attente.');
        }
        if (!$this->isCsrfTokenValid('moderation-jeu-'.$jeu->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jeu->setStatut($decision === 'approuver' ? StatutJeu::Approuve : StatutJeu::Refuse);
        $entityManager->flush();
        $this->addFlash('success', $decision === 'approuver' ? 'Le jeu a été approuvé.' : 'Le jeu a été refusé.');

        return $this->redirectToRoute('app_moderation_jeux');
    }
}
