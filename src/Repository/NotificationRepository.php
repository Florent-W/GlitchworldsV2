<?php
namespace App\Repository;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Notification::class); }
    public function trouverPour(Utilisateur $utilisateur, int $limite = 50): array { return $this->findBy(['utilisateur' => $utilisateur], ['creeeLe' => 'DESC'], $limite); }
    public function compterNonLues(Utilisateur $utilisateur): int { return $this->count(['utilisateur' => $utilisateur, 'lue' => false]); }
}
