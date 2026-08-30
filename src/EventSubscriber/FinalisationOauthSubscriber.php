<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class FinalisationOauthSubscriber implements EventSubscriberInterface
{
    private const ROUTES_AUTORISEES = [
        'app_oauth_finaliser',
        'app_deconnexion',
        'app_conditions_utilisation',
        'app_confidentialite',
        'app_mentions_legales',
    ];

    public function __construct(private Security $security, private UrlGeneratorInterface $urls) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['verrouiller', -10]];
    }

    public function verrouiller(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $utilisateur = $this->security->getUser();
        $route = (string) $event->getRequest()->attributes->get('_route');
        if ($utilisateur instanceof Utilisateur
            && $utilisateur->isFinalisationOauthRequise()
            && !in_array($route, self::ROUTES_AUTORISEES, true)
        ) {
            $event->setResponse(new RedirectResponse($this->urls->generate('app_oauth_finaliser')));
        }
    }
}
