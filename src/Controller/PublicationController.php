<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Form\PublicationType;
use App\Security\PublicationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PublicationController extends AbstractController
{
    #[Route('/communaute/publication', name: 'app_publication_creer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function creer(Request $request, EntityManagerInterface $entityManager): Response
    {
        $publication = new Publication();
        $formulaire = $this->createForm(PublicationType::class, $publication);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $utilisateur = $this->getUser();
            if ($utilisateur instanceof Utilisateur) {
                $publication->setAuteur($utilisateur);
            }
            $entityManager->persist($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Ta publication est en ligne.');
        } else {
            $this->addFlash('danger', 'La publication doit contenir entre 3 et 1 000 caractères.');
        }

        return $this->redirect($this->generateUrl('app_communaute').'#fil');
    }

    #[Route('/communaute/publication/{id}/modifier', name: 'app_publication_modifier')]
    public function modifier(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(PublicationVoter::MODIFIER, $publication);
        $formulaire = $this->createForm(PublicationType::class, $publication);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ta publication a été modifiée.');

            return $this->redirect($this->generateUrl('app_communaute').'#fil');
        }

        return $this->render('communaute/modifier_publication.html.twig', [
            'publication' => $publication,
            'formulaire' => $formulaire,
        ]);
    }

    #[Route('/communaute/publication/{id}/supprimer', name: 'app_publication_supprimer', methods: ['POST'])]
    public function supprimer(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(PublicationVoter::SUPPRIMER, $publication);
        if (!$this->isCsrfTokenValid('supprimer-publication-'.$publication->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($publication);
        $entityManager->flush();
        $this->addFlash('success', 'La publication a été supprimée.');

        return $this->redirect($this->generateUrl('app_communaute').'#fil');
    }

    #[Route('/communaute/publication/{id}/aimer', name: 'app_publication_aimer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function aimer(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('aimer-publication-'.$publication->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $utilisateur = $this->getUser();
        if ($utilisateur instanceof Utilisateur) {
            $publication->estAimePar($utilisateur) ? $publication->retirerAime($utilisateur) : $publication->ajouterAime($utilisateur);
            $entityManager->flush();
        }

        return $this->redirect($this->generateUrl('app_communaute').'#publication-'.$publication->getId());
    }
}
