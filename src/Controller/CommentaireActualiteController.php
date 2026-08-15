<?php

namespace App\Controller;

use App\Entity\CommentaireActualite;
use App\Entity\Utilisateur;
use App\Form\CommentaireActualiteType;
use App\Security\CommentaireActualiteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentaireActualiteController extends AbstractController
{
    #[Route('/actualite/commentaire/{id}/modifier', name: 'app_actualite_commentaire_modifier')]
    public function modifier(CommentaireActualite $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(CommentaireActualiteVoter::MODIFIER, $commentaire);
        $formulaire = $this->createForm(CommentaireActualiteType::class, $commentaire, ['bouton_libelle' => 'Enregistrer']);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ton commentaire a été modifié.');

            return $this->redirigerVersActualite($commentaire);
        }

        return $this->render('actualite/commentaire_modifier.html.twig', ['formulaire' => $formulaire]);
    }

    #[Route('/actualite/commentaire/{id}/supprimer', name: 'app_actualite_commentaire_supprimer', methods: ['POST'])]
    public function supprimer(CommentaireActualite $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(CommentaireActualiteVoter::SUPPRIMER, $commentaire);
        if (!$this->isCsrfTokenValid('supprimer-commentaire-actualite-'.$commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $actualite = $commentaire->getActualite();
        $entityManager->remove($commentaire);
        $entityManager->flush();
        $this->addFlash('success', 'Le commentaire a été supprimé.');

        return $this->redirect($this->generateUrl('app_actualite_voir', ['slug' => $actualite?->getSlug(), 'id' => $actualite?->getId()]).'#commentaires');
    }

    #[Route('/actualite/commentaire/{id}/aimer', name: 'app_actualite_commentaire_aimer', methods: ['POST'])]
    public function aimer(CommentaireActualite $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Connecte-toi pour aimer un commentaire.');
        }
        if (!$this->isCsrfTokenValid('aimer-commentaire-actualite-'.$commentaire->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $commentaire->estAimePar($utilisateur) ? $commentaire->retirerAime($utilisateur) : $commentaire->ajouterAime($utilisateur);
        $entityManager->flush();

        return $this->redirigerVersActualite($commentaire);
    }

    private function redirigerVersActualite(CommentaireActualite $commentaire): Response
    {
        $actualite = $commentaire->getActualite();

        return $this->redirect($this->generateUrl('app_actualite_voir', ['slug' => $actualite?->getSlug(), 'id' => $actualite?->getId()]).'#commentaires');
    }
}
