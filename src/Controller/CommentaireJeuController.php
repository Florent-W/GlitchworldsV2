<?php

namespace App\Controller;

use App\Entity\CommentaireJeu;
use App\Form\CommentaireJeuType;
use App\Security\CommentaireJeuVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentaireJeuController extends AbstractController
{
    #[Route('/commentaire/{id}/modifier', name: 'app_commentaire_modifier')]
    public function modifier(
        CommentaireJeu $commentaire,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(CommentaireJeuVoter::MODIFIER, $commentaire);

        $formulaire = $this->createForm(CommentaireJeuType::class, $commentaire, [
            'bouton_libelle' => 'Enregistrer',
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ton commentaire a été modifié.');
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
    ): Response {
        $this->denyAccessUnlessGranted(CommentaireJeuVoter::SUPPRIMER, $commentaire);

        if (!$this->isCsrfTokenValid('supprimer-commentaire-'.$commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jeu = $commentaire->getJeu();
        $entityManager->remove($commentaire);
        $entityManager->flush();
        $this->addFlash('success', 'Ton commentaire a été supprimé.');

        return $this->redirect($this->generateUrl('app_jeu_show', [
            'slug' => $jeu?->getSlug(),
            'id' => $jeu?->getId(),
        ]).'#commentaires');
    }
}
