<?php

namespace App\Repository;

use App\Entity\Plateforme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plateforme>
 */
class PlateformeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plateforme::class);
    }

    /**
     * @return list<Plateforme>
     */
    public function trouverToutes(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
