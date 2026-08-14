<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Jeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * @return array{moyenne: float|null, total: int}
     */
    public function trouverResume(Jeu $jeu): array
    {
        $resultat = $this->createQueryBuilder('a')
            ->select('AVG(a.note) AS moyenne', 'COUNT(a.id) AS total')
            ->andWhere('a.jeu = :jeu')
            ->setParameter('jeu', $jeu)
            ->getQuery()
            ->getSingleResult();

        return [
            'moyenne' => $resultat['moyenne'] !== null ? round($resultat['moyenne'], 1) : null,
            'total' => $resultat['total'],
        ];
    }
}
