<?php

namespace App\Repository;

use App\Entity\CategorieJeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieJeu>
 */
class CategorieJeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieJeu::class);
    }

    /**
     * @return list<CategorieJeu>
     */
    public function trouverToutes(): array
    {
        return $this->creerRequeteOrdonnee()->getQuery()->getResult();
    }

    public function creerRequeteOrdonnee(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->addSelect("CASE c.slug WHEN 'officiels' THEN 1 WHEN 'rom-hacks' THEN 2 WHEN 'fan-games' THEN 3 WHEN 'mods' THEN 4 WHEN 'recompilations' THEN 5 ELSE 99 END AS HIDDEN gwOrdre")
            ->orderBy('gwOrdre', 'ASC')
            ->addOrderBy('c.nom', 'ASC');
    }
}
