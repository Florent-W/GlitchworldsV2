<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class ParametresController extends AbstractController
{
    #[Route('/parametres', name: 'app_parametres', methods: ['GET'])]
    public function index(): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('parametres/index.html.twig', [
            'utilisateur' => $utilisateur,
            'preferences' => [
                'theme' => $utilisateur->getTheme(),
                'palette' => $utilisateur->getPalette(),
                'mode' => $utilisateur->getMode(),
                'reductionAnimations' => $utilisateur->isReductionAnimations(),
                'notifications' => $utilisateur->getNotifications(),
                'profilPrive' => $utilisateur->isProfilPrive(),
                'contrasteRenforce' => $utilisateur->isContrasteRenforce(),
                'tailleTexte' => $utilisateur->getTailleTexte(),
            ],
        ]);
    }

    #[Route('/parametres/preferences', name: 'app_parametres_preferences', methods: ['POST'])]
    public function enregistrerPreferences(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return new JsonResponse(['error' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur
            ->setTheme((string) ($payload['theme'] ?? $utilisateur->getTheme()))
            ->setReductionAnimations((bool) ($payload['reductionAnimations'] ?? $utilisateur->isReductionAnimations()))
            ->setProfilPrive((bool) ($payload['profilPrive'] ?? $utilisateur->isProfilPrive()))
            ->setContrasteRenforce((bool) ($payload['contrasteRenforce'] ?? $utilisateur->isContrasteRenforce()))
            ->setTailleTexte((string) ($payload['tailleTexte'] ?? $utilisateur->getTailleTexte()));

        if (isset($payload['notifications']) && is_array($payload['notifications'])) {
            $utilisateur->setNotifications($payload['notifications']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'status' => 'ok',
            'preferences' => [
                'theme' => $utilisateur->getTheme(),
                'palette' => $utilisateur->getPalette(),
                'mode' => $utilisateur->getMode(),
                'reductionAnimations' => $utilisateur->isReductionAnimations(),
                'notifications' => $utilisateur->getNotifications(),
                'profilPrive' => $utilisateur->isProfilPrive(),
                'contrasteRenforce' => $utilisateur->isContrasteRenforce(),
                'tailleTexte' => $utilisateur->getTailleTexte(),
            ],
        ]);
    }

    #[Route('/parametres/supprimer', name: 'app_parametres_supprimer', methods: ['POST'])]
    public function supprimerCompte(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher, TokenStorageInterface $tokenStorage, SessionInterface $session): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('suppression-compte-'.$utilisateur->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $motDePasse = (string) $request->request->getString('motDePasse');
        if ($utilisateur->getPassword() && !$hasher->isPasswordValid($utilisateur, $motDePasse)) {
            $this->addFlash('danger', 'Le mot de passe est incorrect.');
            return $this->redirectToRoute('app_parametres');
        }

        $tokenStorage->setToken(null);
        $session->invalidate();
        $entityManager->remove($utilisateur);
        $entityManager->flush();

        $this->addFlash('success', 'Ton compte a bien été supprimé.');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/parametres/sessions/deconnecter-toutes', name: 'app_parametres_sessions_deconnecter_toutes', methods: ['POST'])]
    public function deconnecterToutesSessions(Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('sessions-connectees-'.$utilisateur->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $utilisateur->setSessionsConnectees([]);
        $entityManager->flush();

        return $this->redirectToRoute('app_parametres');
    }
}
