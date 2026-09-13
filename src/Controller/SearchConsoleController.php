<?php

namespace App\Controller;

use App\Service\GoogleSearchConsole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/administration/search-console', name: 'app_administration_search_console_')]
final class SearchConsoleController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'default::OAUTH_GOOGLE_ID')] private readonly ?string $clientId,
        #[Autowire(env: 'default::OAUTH_GOOGLE_SECRET')] private readonly ?string $clientSecret,
    ) {}

    #[Route('/connecter', name: 'connecter', methods: ['GET'])]
    public function connecter(Request $request, GoogleSearchConsole $searchConsole): Response
    {
        if (!$searchConsole->estConfiguree()) {
            $this->addFlash('warning', 'Renseignez GOOGLE_SEARCH_CONSOLE_PROPERTY et les identifiants OAuth Google.');
            return $this->redirectToRoute('app_administration_tableau_de_bord');
        }
        $etat = bin2hex(random_bytes(24));
        $request->getSession()->set('search_console_oauth_state', $etat);
        $urlRetour = $this->generateUrl('app_administration_search_console_verification', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => (string) $this->clientId,
            'redirect_uri' => $urlRetour,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $etat,
        ]);

        return $this->redirect($url);
    }

    #[Route('/verification', name: 'verification', methods: ['GET'])]
    public function verification(Request $request, HttpClientInterface $httpClient, GoogleSearchConsole $searchConsole): Response
    {
        $etat = $request->getSession()->remove('search_console_oauth_state');
        if (!is_string($etat) || !hash_equals($etat, $request->query->getString('state'))) {
            throw $this->createAccessDeniedException('État OAuth Search Console invalide.');
        }
        if ($request->query->has('error')) {
            $this->addFlash('warning', 'La connexion à Search Console a été annulée.');
            return $this->redirectToRoute('app_administration_tableau_de_bord');
        }
        $urlRetour = $this->generateUrl('app_administration_search_console_verification', [], UrlGeneratorInterface::ABSOLUTE_URL);
        try {
            $jetons = $httpClient->request('POST', 'https://oauth2.googleapis.com/token', ['body' => [
                'code' => $request->query->getString('code'),
                'client_id' => (string) $this->clientId,
                'client_secret' => (string) $this->clientSecret,
                'redirect_uri' => $urlRetour,
                'grant_type' => 'authorization_code',
            ]])->toArray();
            $searchConsole->enregistrer($jetons);
            $this->addFlash('success', 'Google Search Console est maintenant connecté.');
        } catch (\Throwable) {
            $this->addFlash('danger', 'Google n’a pas pu finaliser la connexion Search Console. Réessayez.');
        }

        return $this->redirectToRoute('app_administration_tableau_de_bord');
    }

    #[Route('/deconnecter', name: 'deconnecter', methods: ['POST'])]
    public function deconnecter(Request $request, GoogleSearchConsole $searchConsole): Response
    {
        if (!$this->isCsrfTokenValid('deconnecter-search-console', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $searchConsole->deconnecter();
        $this->addFlash('success', 'Google Search Console a été déconnecté.');

        return $this->redirectToRoute('app_administration_tableau_de_bord');
    }
}
