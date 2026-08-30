<?php
namespace App\Security;
use App\Entity\IdentiteOauth;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
final class OauthAuthenticator extends OAuth2Authenticator
{
    public function __construct(private readonly ClientRegistry $clients, private readonly EntityManagerInterface $entityManager, private readonly UrlGeneratorInterface $urls) {}
    public function supports(Request $request): ?bool { return $request->attributes->get('_route') === 'app_oauth_verification'; }
    public function authenticate(Request $request): Passport
    {
        $fournisseur = $request->attributes->getString('provider');
        if ($fournisseur !== 'google') { throw new AuthenticationException('Fournisseur OAuth inconnu.'); }
        $client = $this->clients->getClient('oauth_'.$fournisseur);
        $profil = $client->fetchUserFromToken($this->fetchAccessToken($client)); $identifiant = (string) $profil->getId();
        return new SelfValidatingPassport(new UserBadge($fournisseur.':'.$identifiant, fn (): Utilisateur => $this->chargerUtilisateur($fournisseur, $identifiant, $profil)));
    }
    private function chargerUtilisateur(string $fournisseur, string $identifiant, ResourceOwnerInterface $profil): Utilisateur
    {
        $identites = $this->entityManager->getRepository(IdentiteOauth::class);
        if ($identite = $identites->findOneBy(['fournisseur' => $fournisseur, 'identifiant' => $identifiant])) { return $identite->getUtilisateur(); }
        $donnees = $profil->toArray();
        $email = isset($donnees['email']) && filter_var($donnees['email'], FILTER_VALIDATE_EMAIL) ? strtolower($donnees['email']) : null;
        $emailVerifie = filter_var($donnees['email_verified'] ?? false, FILTER_VALIDATE_BOOL);
        if ($email === null || !$emailVerifie) { throw new CustomUserMessageAuthenticationException('Google n’a pas fourni d’adresse e-mail vérifiée. Utilise l’inscription classique.'); }
        if ($this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email])) { throw new CustomUserMessageAuthenticationException('Un compte utilise déjà cet e-mail. Connecte-toi avec ce compte plutôt qu’avec Google.'); }
        $pseudo = (string) ($donnees['global_name'] ?? $donnees['name'] ?? $donnees['login'] ?? $donnees['username'] ?? ucfirst($fournisseur));
        $pseudo = preg_replace('/\s+/u', '', trim($pseudo)) ?: ucfirst($fournisseur);
        $pseudo = mb_substr($pseudo, 0, 40).'-'.substr(hash('sha256', $fournisseur.$identifiant), 0, 6);
        $utilisateur = (new Utilisateur())->setPseudo($pseudo)->setEmail($email)->setFinalisationOauthRequise(true);
        $this->entityManager->persist($utilisateur);
        $this->entityManager->persist((new IdentiteOauth())->setUtilisateur($utilisateur)->setFournisseur($fournisseur)->setIdentifiant($identifiant)->setEmail($email));
        $this->entityManager->flush(); return $utilisateur;
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $utilisateur = $token->getUser();

        return new RedirectResponse($this->urls->generate(
            $utilisateur instanceof Utilisateur && $utilisateur->isFinalisationOauthRequise()
                ? 'app_oauth_finaliser'
                : 'app_home'
        ));
    }
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response { $request->getSession()->getFlashBag()->add('danger', $exception instanceof CustomUserMessageAuthenticationException ? $exception->getMessageKey() : 'La connexion OAuth a échoué. Réessaie ou utilise ton mot de passe.'); return new RedirectResponse($this->urls->generate('app_connexion')); }
}
