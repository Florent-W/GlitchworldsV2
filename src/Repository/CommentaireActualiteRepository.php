<?php

namespace App\Repository;

use App\Entity\Actualite;
use App\Entity\CommentaireActualite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CommentaireActualite> */
final class CommentaireActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, CommentaireActualite::class); }

    /** @return list<CommentaireActualite> */
    public function trouverRecents(Actualite $actualite, int $limite = 20): array
    {
        return $this->createQueryBuilder('commentaire')
            ->leftJoin('commentaire.auteur', 'auteur')->addSelect('auteur')
            ->andWhere('commentaire.actualite = :actualite')->setParameter('actualite', $actualite)
            ->orderBy('commentaire.dateCommentaire', 'DESC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }
}
