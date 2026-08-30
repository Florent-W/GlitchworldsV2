<?php
namespace App\Repository;
use App\Entity\JeuBibliotheque;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
final class JeuBibliothequeRepository extends ServiceEntityRepository { public function __construct(ManagerRegistry $registry) { parent::__construct($registry, JeuBibliotheque::class); } public function trouverPour(Utilisateur $utilisateur): array { return $this->createQueryBuilder('b')->addSelect('jeu')->join('b.jeu', 'jeu')->andWhere('b.utilisateur = :u')->setParameter('u', $utilisateur)->orderBy('b.ajouteLe', 'DESC')->getQuery()->getResult(); } }
