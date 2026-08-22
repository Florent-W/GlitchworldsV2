<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\Actualite;
use App\Entity\Avis;
use App\Enum\StatutJeu;
use App\Enum\StatutActualite;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\ActualiteRepository;
use App\Repository\AvisRepository;
use App\Form\NoteJeuType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ProgressionUtilisateur;
use App\Service\GestionSucces;
use App\Service\JeuGalerieUploader;
use App\Repository\SignalementRepository;
use App\Enum\StatutSignalement;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Service\JournalModeration;
use App\Service\NotificationAbonnes;
use App\Service\NotificationPropositionModeration;

#[Route('/moderation', name: 'app_moderation_')]
final class ModerationController extends AbstractController
{
    #[Route('', name: 'accueil', methods: ['GET'])]
    public function accueil(): Response
    {
        return $this->redirectToRoute('app_moderation_jeux');
    }

    #[Route('/commentaires', name: 'commentaires', methods: ['GET'])]
    public function commentaires(
        Request $request,
        CommentaireJeuRepository $commentaireJeuRepository,
        CommentaireActualiteRepository $commentaireActualiteRepository,
        AvisRepository $avisRepository,
    ): Response {
        $ongletActif = $request->query->getString('onglet', 'jeux');
        if (!in_array($ongletActif, ['jeux', 'actualites', 'avis'], true)) {
            $ongletActif = 'jeux';
        }

        return $this->render('moderation/commentaires.html.twig', [
            'commentairesJeux' => $commentaireJeuRepository->trouverPourModeration(),
            'commentairesActualites' => $commentaireActualiteRepository->trouverPourModeration(),
            'avisJeux' => $avisRepository->findBy([], ['dateAvis' => 'DESC']),
            'ongletActif' => $ongletActif,
        ]);
    }

    #[Route('/avis/{id}/supprimer', name: 'avis_supprimer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerAvis(
        Avis $avis,
        Request $request,
        EntityManagerInterface $entityManager,
        JournalModeration $journal,
    ): Response {
        if (!$this->isCsrfTokenValid('supprimer-avis-'.$avis->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jeu = $avis->getJeu();
        $moderateur = $this->getUser();
        $journal->ajouter(
            $moderateur instanceof Utilisateur ? $moderateur : null,
            'suppression',
            'avis_jeu',
            $avis->getId(),
            'Suppression de l’avis #'.$avis->getId().' sur '.$avis->getJeu()?->getNom(),
        );
        $entityManager->remove($avis);
        $entityManager->flush();
        $this->addFlash('success', 'L’avis a été supprimé.');

        if ($request->request->getString('_retour') === 'fiche' && $jeu !== null) {
            return $this->redirect($this->generateUrl('app_jeu_show', [
                'slug' => $jeu->getSlug(),
                'id' => $jeu->getId(),
            ]).'#avis-joueurs');
        }

        return $this->redirectToRoute('app_moderation_commentaires', ['onglet' => 'avis']);
    }

    #[Route('/avis/{id}/modifier', name: 'avis_modifier', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierAvis(
        Avis $avis,
        Request $request,
        EntityManagerInterface $entityManager,
        JournalModeration $journal,
    ): Response {
        $retour = $request->query->getString('retour') === 'fiche' ? 'fiche' : 'moderation';
        $formulaire = $this->createForm(NoteJeuType::class, [
            'note' => $avis->getNote(),
            'contenu' => $avis->getContenu(),
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $avis->setNote((float) $formulaire->get('note')->getData());
            $avis->setContenu((string) $formulaire->get('contenu')->getData());
            $moderateur = $this->getUser();
            $journal->ajouter(
                $moderateur instanceof Utilisateur ? $moderateur : null,
                'modification',
                'avis_jeu',
                $avis->getId(),
                'Modification de l’avis #'.$avis->getId().' sur '.$avis->getJeu()?->getNom(),
            );
            $entityManager->flush();
            $this->addFlash('success', 'L’avis a été modifié.');

            if ($retour === 'fiche' && $avis->getJeu() !== null) {
                return $this->redirect($this->generateUrl('app_jeu_show', [
                    'slug' => $avis->getJeu()->getSlug(),
                    'id' => $avis->getJeu()->getId(),
                ]).'#avis-joueurs');
            }

            return $this->redirectToRoute('app_moderation_commentaires', ['onglet' => 'avis']);
        }

        return $this->render('moderation/modifier_avis.html.twig', [
            'avis' => $avis,
            'formulaire' => $formulaire,
            'retour' => $retour,
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
        GestionSucces $gestionSucces,
        JournalModeration $journal,
        NotificationPropositionModeration $notificationProposition,
        NotificationAbonnes $notificationAbonnes,
    ): Response {
        if ($jeu->getStatut() !== StatutJeu::EnAttente) {
            throw $this->createNotFoundException('Cette proposition n’est plus en attente.');
        }
        if (!$this->isCsrfTokenValid('moderation-jeu-'.$jeu->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jeu->setStatut($decision === 'approuver' ? StatutJeu::Approuve : StatutJeu::Refuse);
        if ($decision === 'approuver' && $jeu->getCreateur() !== null) {
            $progression->recompenseJeuApprouve($jeu->getCreateur(), (int) $jeu->getId());
        }
        $moderateur = $this->getUser();
        $journal->ajouter($moderateur instanceof Utilisateur ? $moderateur : null, $decision, 'jeu', $jeu->getId(), ($decision === 'approuver' ? 'Approbation' : 'Refus').' du jeu '.$jeu->getNom());
        $notificationProposition->notifierJeu($jeu, $decision === 'approuver');
        if ($decision === 'approuver') {
            $notificationAbonnes->notifierJeu($jeu);
        }
        $entityManager->flush();
        if ($decision === 'approuver' && $jeu->getCreateur() instanceof Utilisateur) {
            // Notification au créateur via GestionSucces ; pas de flash côté modérateur.
            $gestionSucces->verifier($jeu->getCreateur());
        }
        $this->addFlash('success', $decision === 'approuver' ? 'Le jeu a été approuvé.' : 'Le jeu a été refusé.');

        return $this->redirectToRoute('app_moderation_jeux');
    }

    #[Route('/actualites', name: 'actualites', methods: ['GET'])]
    public function actualites(Request $request, ActualiteRepository $actualiteRepository): Response
    {
        $recherche = trim($request->query->getString('recherche'));
        $statutValeur = $request->query->getString('statut');
        $statut = $statutValeur !== '' ? StatutActualite::tryFrom($statutValeur) : null;
        $resultat = $actualiteRepository->trouverPourModeration($recherche, $statut, $request->query->getInt('page', 1));

        return $this->render('moderation/actualites.html.twig', [
            ...$resultat,
            'recherche' => $recherche,
            'statutSelectionne' => $statut,
            'statuts' => StatutActualite::cases(),
        ]);
    }

    #[Route('/actualites/{id}/{decision}', name: 'actualite_decider', requirements: ['id' => '\d+', 'decision' => 'approuver|refuser'], methods: ['POST'])]
    public function deciderActualite(
        Actualite $actualite,
        string $decision,
        Request $request,
        EntityManagerInterface $entityManager,
        JournalModeration $journal,
        NotificationPropositionModeration $notificationProposition,
        NotificationAbonnes $notificationAbonnes,
        GestionSucces $gestionSucces,
    ): Response {
        if ($actualite->getStatut() !== StatutActualite::EnAttente) {
            throw $this->createNotFoundException('Cette proposition n’est plus en attente.');
        }
        if (!$this->isCsrfTokenValid('moderation-actualite-'.$actualite->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($decision === 'approuver') {
            $actualite->setStatut(StatutActualite::Publiee);
            $actualite->setPublieeLe(new \DateTimeImmutable());
        } else {
            $actualite->setStatut(StatutActualite::Brouillon);
        }

        $moderateur = $this->getUser();
        $journal->ajouter(
            $moderateur instanceof Utilisateur ? $moderateur : null,
            $decision,
            'actualite',
            $actualite->getId(),
            ($decision === 'approuver' ? 'Approbation' : 'Refus').' de l’actualité '.$actualite->getTitre(),
        );
        $notificationProposition->notifierActualite($actualite, $decision === 'approuver');
        if ($decision === 'approuver') {
            $notificationAbonnes->notifierActualite($actualite);
        }
        $entityManager->flush();
        if ($decision === 'approuver' && $actualite->getAuteur() instanceof Utilisateur) {
            $gestionSucces->verifier($actualite->getAuteur());
        }
        $this->addFlash('success', $decision === 'approuver' ? 'L’actualité a été publiée.' : 'L’actualité a été refusée.');

        return $this->redirectToRoute('app_moderation_actualites');
    }

    #[Route('/actualites/{id}', name: 'actualite_voir', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function voirActualite(Actualite $actualite): Response
    {
        return $this->render('moderation/voir_actualite.html.twig', [
            'actualite' => $actualite,
        ]);
    }

    #[Route('/jeux/{id}/statut', name: 'jeu_statut', methods: ['POST'])]
    public function changerStatut(Jeu $jeu, Request $request, EntityManagerInterface $entityManager, JournalModeration $journal): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('statut-jeu-'.$jeu->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $statut = StatutJeu::tryFrom($request->request->getString('statut'));
        if (!$statut) { throw $this->createNotFoundException('Statut inconnu.'); }
        $ancienStatut = $jeu->getStatut()->value;
        $jeu->setStatut($statut);
        $moderateur = $this->getUser();
        $journal->ajouter($moderateur instanceof Utilisateur ? $moderateur : null, 'changement_statut', 'jeu', $jeu->getId(), 'Statut de '.$jeu->getNom().' : '.$ancienStatut.' vers '.$statut->value);
        $entityManager->flush();
        $this->addFlash('success', 'Le statut du jeu a été modifié.');
        return $this->redirectToRoute('app_moderation_jeux');
    }

    #[Route('/jeux/{id}/supprimer', name: 'jeu_supprimer', methods: ['POST'])]
    public function supprimerJeu(Jeu $jeu, Request $request, EntityManagerInterface $entityManager, JeuGalerieUploader $medias, JournalModeration $journal): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('supprimer-jeu-'.$jeu->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $id = (int) $jeu->getId();
        $nom = (string) $jeu->getNom();
        $entityManager->remove($jeu);
        $moderateur = $this->getUser();
        $journal->ajouter($moderateur instanceof Utilisateur ? $moderateur : null, 'suppression', 'jeu', $id, 'Suppression du jeu '.$nom);
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
    public function deciderSignalement(Signalement $signalement, string $decision, Request $request, EntityManagerInterface $entityManager, JournalModeration $journal): Response
    {
        if (!$this->isCsrfTokenValid('signalement-'.$signalement->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $moderateur = $this->getUser();
        if (!$moderateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }
        if ($decision === 'supprimer') {
            $cible = $signalement->getCommentaireJeu() ?? $signalement->getCommentaireActualite() ?? $signalement->getPublication();
            if ($cible) { $entityManager->remove($cible); }
        }
        $signalement->cloturer($decision === 'rejeter' ? StatutSignalement::Rejete : StatutSignalement::Traite, $moderateur);
        $journal->ajouter($moderateur, $decision, 'signalement', $signalement->getId(), 'Signalement #'.$signalement->getId().' : '.$decision);
        $entityManager->flush();
        $this->addFlash('success', $decision === 'supprimer' ? 'Le contenu a été supprimé et le signalement traité.' : 'Le signalement a été clôturé.');
        return $this->redirectToRoute('app_moderation_signalements');
    }
}
