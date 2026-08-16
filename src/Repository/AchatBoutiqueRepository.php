<?php

namespace App\Repository;

use App\Entity\AchatBoutique;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AchatBoutiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, AchatBoutique::class); }
    /** @return list<AchatBoutique> */
    public function trouverPourUtilisateur(Utilisateur $utilisateur): array { return $this->createQueryBuilder('a')->addSelect('article')->join('a.article', 'article')->andWhere('a.utilisateur = :utilisateur')->setParameter('utilisateur', $utilisateur)->orderBy('a.acheteLe', 'DESC')->getQuery()->getResult(); }
}
