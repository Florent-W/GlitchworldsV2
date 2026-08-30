<?php
namespace App\Service;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
final readonly class CentreNotifications
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    public function ajouter(Utilisateur $utilisateur, string $titre, string $message, string $icone = 'bell-fill', ?string $url = null): Notification
    {
        $notification = (new Notification())->setUtilisateur($utilisateur)->setTitre($titre)->setMessage($message)->setIcone($icone)->setUrl($url);
        $this->entityManager->persist($notification);
        return $notification;
    }
}
