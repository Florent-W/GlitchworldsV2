<?php
namespace App\EventSubscriber;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
final readonly class PresenceSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security, private EntityManagerInterface $entityManager) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::RESPONSE => 'actualiser']; }
    public function actualiser(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !($utilisateur = $this->security->getUser()) instanceof Utilisateur) { return; }
        if ($utilisateur->getDerniereActivite() && $utilisateur->getDerniereActivite() > new \DateTimeImmutable('-1 minute')) { return; }
        $utilisateur->setDerniereActivite(new \DateTimeImmutable()); $this->entityManager->flush();
    }
}
