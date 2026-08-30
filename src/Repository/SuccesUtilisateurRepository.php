<?php
namespace App\Repository;
use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class SuccesUtilisateurRepository extends ServiceEntityRepository { public function __construct(ManagerRegistry $registry) { parent::__construct($registry, SuccesUtilisateur::class); } public function trouverPour(Utilisateur $utilisateur): array { return $this->createQueryBuilder('d')->addSelect('s')->join('d.succes', 's')->andWhere('d.utilisateur = :u')->setParameter('u', $utilisateur)->orderBy('d.debloqueLe', 'DESC')->getQuery()->getResult(); } }
