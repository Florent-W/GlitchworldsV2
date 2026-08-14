<?php

namespace App\Repository;

use App\Entity\Langue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Langue>
 */
class LangueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Langue::class);
    }

    /**
     * @return list<Langue>
     */
    public function trouverToutes(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
