<?php

namespace App\Repository;

use App\Entity\ListeJeux;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeJeux>
 */
final class ListeJeuxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeJeux::class);
    }

    /** @return list<ListeJeux> */
    public function trouverPour(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('jeux', 'categorie', 'genres')
            ->leftJoin('l.jeux', 'jeux')
            ->leftJoin('jeux.categorie', 'categorie')
            ->leftJoin('jeux.genres', 'genres')
            ->andWhere('l.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('l.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
