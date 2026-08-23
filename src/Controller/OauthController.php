<?php
namespace App\Controller;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class OauthController extends AbstractController
{
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
}
