<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Form\PublicationType;
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
}
