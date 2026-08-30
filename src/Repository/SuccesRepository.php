<?php

namespace App\Repository;

use App\Entity\Succes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Succes>
 */
final class SuccesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Succes::class);
    }

    /** @return list<Succes> */
    public function trouverTousParDifficulte(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.points', 'ASC')
            ->addOrderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
