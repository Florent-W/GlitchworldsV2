<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecuriteController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = new Utilisateur();
        $formulaire = $this->createForm(InscriptionType::class, $utilisateur);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $motDePasseClair = (string) $formulaire->get('motDePasseClair')->getData();
            $utilisateur->setPassword($hasher->hashPassword($utilisateur, $motDePasseClair));

            $entityManager->persist($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Ton compte a été créé. Tu peux maintenant te connecter.');

            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('securite/inscription.html.twig', ['formulaire' => $formulaire]);
    }

    #[Route('/connexion', name: 'app_connexion')]
    public function connexion(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('securite/connexion.html.twig', [
            'derniereAdresse' => $authenticationUtils->getLastUsername(),
            'erreur' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion')]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette route est interceptée par le pare-feu Symfony.');
    }
}
