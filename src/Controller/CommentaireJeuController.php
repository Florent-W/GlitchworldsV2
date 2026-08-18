<?php

namespace App\Controller;

use App\Entity\CommentaireJeu;
use App\Entity\Utilisateur;
use App\Form\CommentaireJeuType;
use App\Security\CommentaireJeuVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\ProgressionUtilisateur;
use App\Service\JournalModeration;

final class CommentaireJeuController extends AbstractController
{
    #[Route('/commentaire/{id}/repondre', name: 'app_commentaire_repondre', methods: ['POST'])]
    public function repondre(
        CommentaireJeu $commentaire,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        ProgressionUtilisateur $progression,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Connecte-toi pour répondre.');
        }

        $parent = $commentaire->getParent() ?? $commentaire;
        if (!$this->isCsrfTokenValid('repondre-commentaire-'.$parent->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $reponse = (new CommentaireJeu())
            ->setJeu($parent->getJeu())
            ->setAuteur($utilisateur)
            ->setParent($parent)
            ->setContenu($request->request->getString('contenu'));
        $erreurs = $validator->validate($reponse);

        if ($erreurs->count() > 0) {
            $this->addFlash('danger', $erreurs[0]->getMessage());
        } else {
            $entityManager->persist($reponse);
            $recompenseAccordee = $progression->recompenseCommentaire($utilisateur, 'reponse-jeu:'.$parent->getId().':'.hash('sha256', mb_strtolower(trim($reponse->getContenu()))));
            $entityManager->flush();
            $this->addFlash('success', 'Ta réponse a été publiée.'.($recompenseAccordee ? ' +10 XP et +5 points.' : ''));
        }

        $jeu = $parent->getJeu();

        return $this->redirect($this->generateUrl('app_jeu_show', [
            'slug' => $jeu?->getSlug(),
            'id' => $jeu?->getId(),
        ]).'#commentaire-'.$parent->getId());
    }

    #[Route('/commentaire/{id}/modifier', name: 'app_commentaire_modifier')]
    public function modifier(
        CommentaireJeu $commentaire,
        Request $request,
        EntityManagerInterface $entityManager,
        JournalModeration $journal,
    ): Response {
        $this->denyAccessUnlessGranted(CommentaireJeuVoter::MODIFIER, $commentaire);

        $formulaire = $this->createForm(CommentaireJeuType::class, $commentaire, [
            'bouton_libelle' => 'Enregistrer',
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            if ($this->isGranted('ROLE_MODERATEUR')) { $moderateur = $this->getUser(); $journal->ajouter($moderateur instanceof Utilisateur ? $moderateur : null, 'modification', 'commentaire_jeu', $commentaire->getId(), 'Modification du commentaire #'.$commentaire->getId()); }
            $entityManager->flush();
            $this->addFlash('success', 'Ton commentaire a été modifié.');
            if ('moderation' === $request->query->getString('retour')) {
                return $this->redirectToRoute('app_moderation_commentaires');
            }
            $jeu = $commentaire->getJeu();

            return $this->redirect($this->generateUrl('app_jeu_show', [
                'slug' => $jeu?->getSlug(),
                'id' => $jeu?->getId(),
            ]).'#commentaires');
        }

        return $this->render('commentaire_jeu/modifier.html.twig', [
            'commentaire' => $commentaire,
            'formulaire' => $formulaire,
        ]);
    }

    #[Route('/commentaire/{id}/supprimer', name: 'app_commentaire_supprimer', methods: ['POST'])]
    public function supprimer(
        CommentaireJeu $commentaire,
        Request $request,
        EntityManagerInterface $entityManager,
        JournalModeration $journal,
    ): Response {
        $this->denyAccessUnlessGranted(CommentaireJeuVoter::SUPPRIMER, $commentaire);

        if (!$this->isCsrfTokenValid('supprimer-commentaire-'.$commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jeu = $commentaire->getJeu();
        if ($this->isGranted('ROLE_MODERATEUR')) { $moderateur = $this->getUser(); $journal->ajouter($moderateur instanceof Utilisateur ? $moderateur : null, 'suppression', 'commentaire_jeu', $commentaire->getId(), 'Suppression du commentaire #'.$commentaire->getId()); }
        $entityManager->remove($commentaire);
        $entityManager->flush();
        $this->addFlash('success', 'Ton commentaire a été supprimé.');

        if ('moderation' === $request->request->getString('_retour')) {
            return $this->redirectToRoute('app_moderation_commentaires');
        }

        return $this->redirect($this->generateUrl('app_jeu_show', [
            'slug' => $jeu?->getSlug(),
            'id' => $jeu?->getId(),
        ]).'#commentaires');
    }

    #[Route('/commentaire/{id}/aimer', name: 'app_commentaire_aimer', methods: ['POST'])]
    public function aimer(CommentaireJeu $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Connecte-toi pour aimer un commentaire.');
        }
        if (!$this->isCsrfTokenValid('aimer-commentaire-'.$commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $commentaire->estAimePar($utilisateur) ? $commentaire->retirerAime($utilisateur) : $commentaire->ajouterAime($utilisateur);
        $entityManager->flush();
        $jeu = $commentaire->getJeu();

        return $this->redirect($this->generateUrl('app_jeu_show', ['slug' => $jeu?->getSlug(), 'id' => $jeu?->getId()]).'#commentaires');
    }
}
