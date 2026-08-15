<?php

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Publication> */
final class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /** @return list<Publication> */
    public function trouverDernieres(int $limite = 20): array
    {
        return $this->createQueryBuilder('publication')
            ->leftJoin('publication.auteur', 'auteur')->addSelect('auteur')
            ->orderBy('publication.publieeLe', 'DESC')
            ->setMaxResults(max(1, min(50, $limite)))
            ->getQuery()->getResult();
    }
}
