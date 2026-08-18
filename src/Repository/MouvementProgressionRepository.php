<?php
namespace App\Repository;
use App\Entity\MouvementProgression;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class MouvementProgressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, MouvementProgression::class); }
    public function existe(Utilisateur $utilisateur, string $cle): bool { return (bool) $this->findOneBy(['utilisateur' => $utilisateur, 'cleSource' => $cle]); }
    public function compterDepuis(Utilisateur $utilisateur, string $categorie, \DateTimeImmutable $depuis): int { return (int) $this->createQueryBuilder('m')->select('COUNT(m.id)')->andWhere('m.utilisateur = :u')->andWhere('m.categorie = :c')->andWhere('m.creeLe >= :d')->setParameter('u', $utilisateur)->setParameter('c', $categorie)->setParameter('d', $depuis)->getQuery()->getSingleScalarResult(); }
    public function trouverPour(Utilisateur $utilisateur, int $limite = 100): array { return $this->findBy(['utilisateur' => $utilisateur], ['creeLe' => 'DESC'], $limite); }
}
