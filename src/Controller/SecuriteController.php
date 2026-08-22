<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\InscriptionType;
use App\Form\CompteType;
use App\Form\MotDePasseType;
use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use App\Service\ImageProfilUploader;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class SecuriteController extends AbstractController
{
    use AnnonceSuccesTrait;

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
    public function compte(JeuRepository $jeuRepository, ActualiteRepository $actualiteRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('securite/compte.html.twig', [
            'propositions' => $jeuRepository->trouverPropositions($utilisateur),
            'propositionsActualites' => $actualiteRepository->trouverPropositions($utilisateur),
        ]);
    }

    #[Route('/mon-compte/modifier', name: 'app_compte_modifier')]
    public function modifierCompte(Request $request, EntityManagerInterface $entityManager, ImageProfilUploader $uploader, GestionSucces $gestionSucces): Response
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
            $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
            $this->addFlash('success', 'Ton profil a été mis à jour.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('securite/modifier_compte.html.twig', [
            'formulaire' => $formulaire,
        ]);
    }

    #[Route('/mon-compte/avatar', name: 'app_compte_avatar', methods: ['POST'])]
    public function modifierAvatar(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageProfilUploader $uploader,
        ValidatorInterface $validator,
        GestionSucces $gestionSucces,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('avatar-profil', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $fichier = $request->files->get('avatar');
        if (!$fichier instanceof UploadedFile) {
            $this->addFlash('danger', 'Aucune image sélectionnée.');

            return $this->redirectToRoute('app_profil', ['id' => $utilisateur->getId()]);
        }

        $violations = $validator->validate($fichier, [
            new File(maxSize: '5M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']),
        ]);
        if (\count($violations) > 0) {
            $this->addFlash('danger', (string) $violations->get(0)->getMessage());

            return $this->redirectToRoute('app_profil', ['id' => $utilisateur->getId()]);
        }

        $utilisateur->setAvatar($uploader->enregistrer($fichier, $utilisateur, 'avatar'));
        $entityManager->flush();
        $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
        $this->addFlash('success', 'Ta photo de profil a été mise à jour.');

        return $this->redirectToRoute('app_profil', ['id' => $utilisateur->getId()]);
    }

    #[Route('/mon-compte/banniere', name: 'app_compte_banniere', methods: ['POST'])]
    public function modifierBanniere(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageProfilUploader $uploader,
        ValidatorInterface $validator,
        GestionSucces $gestionSucces,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('banniere-profil', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $fichier = $request->files->get('banniere');
        if (!$fichier instanceof UploadedFile) {
            $this->addFlash('danger', 'Aucune image sélectionnée.');

            return $this->redirectToRoute('app_profil', ['id' => $utilisateur->getId()]);
        }

        $violations = $validator->validate($fichier, [
            new File(maxSize: '8M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']),
        ]);
        if (\count($violations) > 0) {
            $this->addFlash('danger', (string) $violations->get(0)->getMessage());

            return $this->redirectToRoute('app_profil', ['id' => $utilisateur->getId()]);
        }

        $utilisateur->setBanniere($uploader->enregistrer($fichier, $utilisateur, 'banniere'));
        $entityManager->flush();
        $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
        $this->addFlash('success', 'Ta bannière de profil a été mise à jour.');

        return $this->redirectToRoute('app_profil', ['id' => $utilisateur->getId()]);
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

    #[Route('/mot-de-passe-oublie', name: 'app_mot_de_passe_oublie')]
    public function motDePasseOublie(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, #[Autowire(service: 'limiter.password_reset')] RateLimiterFactory $passwordResetLimiter): Response
    {
        $formulaire = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
            ->add('envoyer', SubmitType::class, ['label' => 'Envoyer le lien', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            if (!$passwordResetLimiter->create($request->getClientIp() ?? 'inconnue')->consume()->isAccepted()) {
                $this->addFlash('danger', 'Trop de demandes ont été envoyées. Réessaie dans quelques minutes.');
                return $this->redirectToRoute('app_mot_de_passe_oublie');
            }
            $email = strtolower(trim((string) $formulaire->get('email')->getData()));
            $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
            if ($utilisateur instanceof Utilisateur) {
                $jeton = bin2hex(random_bytes(32));
                $utilisateur->definirJetonReinitialisation(hash('sha256', $jeton), new \DateTimeImmutable('+1 hour'));
                $entityManager->flush();
                $lien = $this->generateUrl('app_reinitialiser_mot_de_passe', ['jeton' => $jeton], UrlGeneratorInterface::ABSOLUTE_URL);
                $mailer->send((new Email())
                    ->from('noreply@glitchworlds.local')
                    ->to($email)
                    ->subject('Réinitialisation de ton mot de passe Glitchworlds')
                    ->text("Utilise ce lien dans l’heure qui suit :\n\n".$lien."\n\nSi tu n’es pas à l’origine de cette demande, ignore ce message."));
            }
            $this->addFlash('success', 'Si un compte correspond à cette adresse, un lien vient d’être envoyé.');
            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('securite/mot_de_passe_oublie.html.twig', ['formulaire' => $formulaire]);
    }

    #[Route('/reinitialiser-mot-de-passe/{jeton}', name: 'app_reinitialiser_mot_de_passe', requirements: ['jeton' => '[a-f0-9]{64}'])]
    public function reinitialiserMotDePasse(string $jeton, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $utilisateur = $entityManager->getRepository(Utilisateur::class)->findOneBy(['jetonReinitialisation' => hash('sha256', $jeton)]);
        if (!$utilisateur instanceof Utilisateur || $utilisateur->getExpirationJetonReinitialisation() === null || $utilisateur->getExpirationJetonReinitialisation() < new \DateTimeImmutable()) {
            $this->addFlash('danger', 'Ce lien est invalide ou a expiré.');
            return $this->redirectToRoute('app_mot_de_passe_oublie');
        }

        $formulaire = $this->createFormBuilder()
            ->add('motDePasse', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Nouveau mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe doivent être identiques.',
                'constraints' => [new Assert\Length(min: 8, max: 4096)],
            ])
            ->add('enregistrer', SubmitType::class, ['label' => 'Changer mon mot de passe', 'attr' => ['class' => 'btn btn-primary']])
            ->getForm();
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $utilisateur
                ->setPassword($hasher->hashPassword($utilisateur, (string) $formulaire->get('motDePasse')->getData()))
                ->definirJetonReinitialisation(null, null);
            $entityManager->flush();
            $this->addFlash('success', 'Ton mot de passe a été changé. Tu peux te connecter.');
            return $this->redirectToRoute('app_connexion');
        }

        return $this->render('securite/reinitialiser_mot_de_passe.html.twig', ['formulaire' => $formulaire]);
    }

    #[Route('/deconnexion', name: 'app_deconnexion')]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette route est interceptée par le pare-feu Symfony.');
    }
}
