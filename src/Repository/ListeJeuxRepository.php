<?php
namespace App\Repository;
use App\Entity\ListeJeux;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class ListeJeuxRepository extends ServiceEntityRepository { public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ListeJeux::class); } public function trouverPour(Utilisateur $utilisateur): array { return $this->createQueryBuilder('l')->addSelect('jeux')->leftJoin('l.jeux', 'jeux')->andWhere('l.utilisateur = :u')->setParameter('u', $utilisateur)->orderBy('l.creeLe', 'DESC')->getQuery()->getResult(); } }
