<?php
namespace App\EventSubscriber;
use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Entity\VuePage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
final class StatistiquesVisiteSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em, private Security $security, private LoggerInterface $logger, #[Autowire('%kernel.secret%')] private string $secret) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::RESPONSE => ['enregistrer', -100]]; }
    public function enregistrer(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) { return; }
        $requete = $event->getRequest(); $reponse = $event->getResponse();
        if (!$requete->isMethod('GET') || !$reponse->isSuccessful() || !str_contains((string) $reponse->headers->get('Content-Type'), 'text/html')) { return; }
        $route = (string) $requete->attributes->get('_route');
        if ($route === '' || str_starts_with($route, '_') || str_starts_with($route, 'app_administration_') || str_starts_with($route, 'app_moderation_')) { return; }
        $type = 'page'; $id = null;
        if (($jeu = $requete->attributes->get('jeu')) instanceof Jeu) { $type = 'jeu'; $id = $jeu->getId(); }
        elseif (($actualite = $requete->attributes->get('actualite')) instanceof Actualite) { $type = 'actualite'; $id = $actualite->getId(); }
        $empreinte = ($requete->getClientIp() ?? '').'|'.$requete->headers->get('User-Agent', '');
        $utilisateur = $this->security->getUser();
        try {
            $this->em->persist(new VuePage($requete->getPathInfo(), $type, $id, hash_hmac('sha256', $empreinte, $this->secret), $utilisateur instanceof Utilisateur ? $utilisateur : null));
            $this->em->flush();
        } catch (\Throwable $erreur) {
            $this->logger->warning('La vue de page n’a pas pu être enregistrée.', ['exception' => $erreur]);
        }
    }
}
