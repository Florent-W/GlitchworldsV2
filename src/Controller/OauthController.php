<?php
namespace App\Controller;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\ImageProfilUploader;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;
final class OauthController extends AbstractController
{
    private const VERSION_CONDITIONS = '2026-08-23';

    public function __construct(
        #[Autowire(env: 'default::OAUTH_GOOGLE_ID')]
        private readonly string $googleClientId,
    ) {}

    #[Route('/oauth/google', name: 'app_oauth_connexion', methods: ['GET'])]
    public function connexion(ClientRegistry $clients): Response
    {
        if ($this->googleClientId === '') {
            $this->addFlash('warning', 'La connexion Google n’est pas encore configurée.');

            return $this->redirectToRoute('app_connexion');
        }

        return $clients->getClient('oauth_google')->redirect(['openid', 'profile', 'email']);
    }

    #[Route('/oauth/google/verification', name: 'app_oauth_verification', defaults: ['provider' => 'google'], methods: ['GET'])]
    public function verification(): never
    {
        throw new \LogicException('Cette route est interceptée par l’authenticator OAuth.');
    }

    #[Route('/oauth/finaliser', name: 'app_oauth_finaliser', methods: ['GET', 'POST'])]
    public function finaliser(Request $request, EntityManagerInterface $entityManager, UtilisateurRepository $utilisateurs, ImageProfilUploader $uploader, ValidatorInterface $validator): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return $this->redirectToRoute('app_connexion');
        }
        if ($utilisateur->getConditionsAccepteesLe() !== null) {
            return $this->redirectToRoute('app_home');
        }

        $erreurs = [];
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('finaliser-oauth-'.$utilisateur->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $pseudo = trim($request->request->getString('pseudo'));
            if (mb_strlen($pseudo) < 3 || mb_strlen($pseudo) > 50) {
                $erreurs[] = 'Le pseudo doit contenir entre 3 et 50 caractères.';
            }
            if (preg_match('/\s/u', $pseudo)) {
                $erreurs[] = 'Le pseudo ne doit contenir aucun espace.';
            }
            if (!$utilisateurs->pseudoEstDisponible($pseudo, $utilisateur->getId())) {
                $erreurs[] = 'Ce pseudo est déjà utilisé.';
            }
            $avatar = $request->files->get('avatar');
            if ($avatar instanceof UploadedFile) {
                $violations = $validator->validate($avatar, new File(maxSize: '2M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']));
                foreach ($violations as $violation) {
                    $erreurs[] = (string) $violation->getMessage();
                }
            }
            if (!$request->request->getBoolean('conditions')) {
                $erreurs[] = 'Tu dois accepter les conditions d’utilisation pour créer ton compte.';
            }

            if ($erreurs === []) {
                $utilisateur->setPseudo($pseudo)->accepterConditions(self::VERSION_CONDITIONS);
                if ($avatar instanceof UploadedFile) {
                    $utilisateur->setAvatar($uploader->enregistrer($avatar, $utilisateur, 'avatar'));
                }
                $entityManager->flush();
                $this->addFlash('success', 'Ton compte Google est prêt. Bienvenue sur Glitchworlds !');

                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('securite/finaliser_oauth.html.twig', [
            'utilisateur' => $utilisateur,
            'erreurs' => $erreurs,
            'pseudoSuggere' => preg_replace('/\s+/u', '', preg_replace('/-[a-f0-9]{6}$/', '', $utilisateur->getPseudo()) ?? '') ?: $utilisateur->getPseudo(),
        ]);
    }
}
