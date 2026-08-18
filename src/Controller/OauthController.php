<?php
namespace App\Controller;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class OauthController extends AbstractController
{
    private const FOURNISSEURS = ['google', 'discord', 'github'];
    #[Route('/oauth/{provider}', name: 'app_oauth_connexion', requirements: ['provider' => 'google|discord|github'], methods: ['GET'])]
    public function connexion(string $provider, ClientRegistry $clients): Response
    {
        $scopes = match ($provider) { 'google' => ['openid', 'profile', 'email'], 'github' => ['read:user', 'user:email'], default => ['identify', 'email'] };
        return $clients->getClient('oauth_'.$provider)->redirect($scopes);
    }
    #[Route('/oauth/{provider}/verification', name: 'app_oauth_verification', requirements: ['provider' => 'google|discord|github'], methods: ['GET'])]
    public function verification(): never { throw new \LogicException('Cette route est interceptée par l’authenticator OAuth.'); }
}
