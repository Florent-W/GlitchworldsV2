<?php
namespace App\EventSubscriber;
use App\Entity\Utilisateur;
use App\Service\ProgressionUtilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
final readonly class PresenceSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security, private EntityManagerInterface $entityManager, private ProgressionUtilisateur $progression) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::RESPONSE => 'actualiser']; }
    public function actualiser(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !($utilisateur = $this->security->getUser()) instanceof Utilisateur) { return; }
        $maintenant = new \DateTimeImmutable();
        $procedureInactivite = $utilisateur->getInactiviteAvertieLe() !== null || $utilisateur->getSuppressionProgrammeeLe() !== null;
        if (!$procedureInactivite && $utilisateur->getDerniereActivite() && $utilisateur->getDerniereActivite() > $maintenant->modify('-1 minute')) { return; }
        $utilisateur->setDerniereActivite($maintenant)->annulerSuppressionPourInactivite();
        $this->progression->recompenseAnciennete($utilisateur);
        $this->entityManager->flush();
    }
}
