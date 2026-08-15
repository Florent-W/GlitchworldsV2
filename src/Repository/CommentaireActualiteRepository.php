<?php

namespace App\Repository;

use App\Entity\Actualite;
use App\Entity\CommentaireActualite;
use App\Enum\StatutActualite;
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
            ->leftJoin('commentaire.aimePar', 'aimePar')->addSelect('aimePar')
            ->andWhere('commentaire.actualite = :actualite')->setParameter('actualite', $actualite)
            ->orderBy('commentaire.dateCommentaire', 'DESC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }

    /** @return list<CommentaireActualite> */
    public function trouverDerniersPublics(int $limite = 8): array
    {
        return $this->createQueryBuilder('commentaire')
            ->leftJoin('commentaire.auteur', 'auteur')->addSelect('auteur')
            ->innerJoin('commentaire.actualite', 'actualite')->addSelect('actualite')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->orderBy('commentaire.dateCommentaire', 'DESC')
            ->setMaxResults(max(1, min(20, $limite)))
            ->getQuery()->getResult();
    }

    public function compterPublics(): int
    {
        return (int) $this->createQueryBuilder('commentaire')->select('COUNT(commentaire.id)')
            ->innerJoin('commentaire.actualite', 'actualite')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->getQuery()->getSingleScalarResult();
    }
}
