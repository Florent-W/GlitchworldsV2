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
use App\Service\ProgressionUtilisateur;
use App\Service\JeuGalerieUploader;
use App\Repository\SignalementRepository;
use App\Enum\StatutSignalement;
use App\Entity\Signalement;
use App\Entity\Utilisateur;

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
    public function jeux(Request $request, JeuRepository $jeuRepository): Response
    {
        $recherche = trim($request->query->getString('recherche'));
        $statutValeur = $request->query->getString('statut');
        $statut = $statutValeur !== '' ? StatutJeu::tryFrom($statutValeur) : null;
        $resultat = $jeuRepository->trouverPourAdministration($recherche, $statut, $request->query->getInt('page', 1));
        return $this->render('moderation/jeux.html.twig', [
            ...$resultat,
            'recherche' => $recherche,
            'statutSelectionne' => $statut,
            'statuts' => StatutJeu::cases(),
        ]);
    }

    #[Route('/jeux/{id}', name: 'jeu_voir', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function voir(Jeu $jeu): Response
    {
        return $this->render('moderation/voir_jeu.html.twig', ['jeu' => $jeu]);
    }

    #[Route('/jeux/{id}/{decision}', name: 'jeu_decider', requirements: ['decision' => 'approuver|refuser'], methods: ['POST'])]
    public function decider(
        Jeu $jeu,
        string $decision,
        Request $request,
        EntityManagerInterface $entityManager,
        ProgressionUtilisateur $progression,
    ): Response {
        if ($jeu->getStatut() !== StatutJeu::EnAttente) {
            throw $this->createNotFoundException('Cette proposition n’est plus en attente.');
        }
        if (!$this->isCsrfTokenValid('moderation-jeu-'.$jeu->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jeu->setStatut($decision === 'approuver' ? StatutJeu::Approuve : StatutJeu::Refuse);
        if ($decision === 'approuver' && $jeu->getCreateur() !== null) {
            $progression->recompenseJeuApprouve($jeu->getCreateur());
        }
        $entityManager->flush();
        $this->addFlash('success', $decision === 'approuver' ? 'Le jeu a été approuvé.' : 'Le jeu a été refusé.');

        return $this->redirectToRoute('app_moderation_jeux');
    }

    #[Route('/jeux/{id}/statut', name: 'jeu_statut', methods: ['POST'])]
    public function changerStatut(Jeu $jeu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('statut-jeu-'.$jeu->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $statut = StatutJeu::tryFrom($request->request->getString('statut'));
        if (!$statut) { throw $this->createNotFoundException('Statut inconnu.'); }
        $jeu->setStatut($statut);
        $entityManager->flush();
        $this->addFlash('success', 'Le statut du jeu a été modifié.');
        return $this->redirectToRoute('app_moderation_jeux');
    }

    #[Route('/jeux/{id}/supprimer', name: 'jeu_supprimer', methods: ['POST'])]
    public function supprimerJeu(Jeu $jeu, Request $request, EntityManagerInterface $entityManager, JeuGalerieUploader $medias): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('supprimer-jeu-'.$jeu->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $id = (int) $jeu->getId();
        $entityManager->remove($jeu);
        $entityManager->flush();
        $medias->supprimerMedias($id);
        $this->addFlash('success', 'Le jeu et ses contenus associés ont été supprimés.');
        return $this->redirectToRoute('app_moderation_jeux');
    }

    #[Route('/signalements', name: 'signalements', methods: ['GET'])]
    public function signalements(Request $request, SignalementRepository $repository): Response
    {
        $statut = ($valeur = $request->query->getString('statut')) !== '' ? StatutSignalement::tryFrom($valeur) : StatutSignalement::EnAttente;
        return $this->render('moderation/signalements.html.twig', ['signalements' => $repository->trouverPourModeration($statut), 'statutSelectionne' => $statut, 'statuts' => StatutSignalement::cases()]);
    }

    #[Route('/signalements/{id}/{decision}', name: 'signalement_decider', requirements: ['decision' => 'traiter|rejeter|supprimer'], methods: ['POST'])]
    public function deciderSignalement(Signalement $signalement, string $decision, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('signalement-'.$signalement->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $moderateur = $this->getUser();
        if (!$moderateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }
        if ($decision === 'supprimer') {
            $cible = $signalement->getCommentaireJeu() ?? $signalement->getCommentaireActualite() ?? $signalement->getPublication();
            if ($cible) { $entityManager->remove($cible); }
        }
        $signalement->cloturer($decision === 'rejeter' ? StatutSignalement::Rejete : StatutSignalement::Traite, $moderateur);
        $entityManager->flush();
        $this->addFlash('success', $decision === 'supprimer' ? 'Le contenu a été supprimé et le signalement traité.' : 'Le signalement a été clôturé.');
        return $this->redirectToRoute('app_moderation_signalements');
    }
}
