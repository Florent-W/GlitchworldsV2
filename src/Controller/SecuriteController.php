<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\InscriptionType;
use App\Form\CompteType;
use App\Form\MotDePasseType;
use App\Repository\JeuRepository;
use App\Service\ImageProfilUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    #[Route('/mon-compte', name: 'app_compte')]
    public function compte(JeuRepository $jeuRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('securite/compte.html.twig', [
            'propositions' => $jeuRepository->trouverPropositions($utilisateur),
        ]);
    }

    #[Route('/mon-compte/modifier', name: 'app_compte_modifier')]
    public function modifierCompte(Request $request, EntityManagerInterface $entityManager, ImageProfilUploader $uploader): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $formulaire = $this->createForm(CompteType::class, $utilisateur);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $avatar = $formulaire->get('avatarFichier')->getData();
            if ($avatar instanceof UploadedFile) {
                $utilisateur->setAvatar($uploader->enregistrer($avatar, $utilisateur, 'avatar'));
            }
            $banniere = $formulaire->get('banniereFichier')->getData();
            if ($banniere instanceof UploadedFile) {
                $utilisateur->setBanniere($uploader->enregistrer($banniere, $utilisateur, 'banniere'));
            }
            $entityManager->flush();
            $this->addFlash('success', 'Ton profil a été mis à jour.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('securite/modifier_compte.html.twig', [
            'formulaire' => $formulaire,
        ]);
    }

    #[Route('/mon-compte/mot-de-passe', name: 'app_compte_mot_de_passe')]
    public function modifierMotDePasse(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $formulaire = $this->createForm(MotDePasseType::class);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $nouveauMotDePasse = (string) $formulaire->get('nouveauMotDePasse')->getData();
            $utilisateur->setPassword($hasher->hashPassword($utilisateur, $nouveauMotDePasse));
            $entityManager->flush();
            $this->addFlash('success', 'Ton mot de passe a été modifié.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('securite/modifier_mot_de_passe.html.twig', [
            'formulaire' => $formulaire,
        ]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion')]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette route est interceptée par le pare-feu Symfony.');
    }
}
