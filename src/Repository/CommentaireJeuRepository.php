<?php

namespace App\Repository;

use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Enum\StatutJeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentaireJeu>
 */
class CommentaireJeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentaireJeu::class);
    }

    /**
     * @return list<CommentaireJeu>
     */
    public function trouverRecents(Jeu $jeu, int $limite = 5): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.auteur', 'auteur')
            ->addSelect('auteur')
            ->leftJoin('c.aimePar', 'aimePar')
            ->addSelect('aimePar')
            ->andWhere('c.jeu = :jeu')
            ->setParameter('jeu', $jeu)
            ->orderBy('c.dateCommentaire', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    public function compterPourJeu(Jeu $jeu): int
    {
        return $this->count(['jeu' => $jeu]);
    }

    /** @return list<CommentaireJeu> */
    public function trouverDerniersPublics(int $limite = 8): array
    {
        return $this->createQueryBuilder('commentaire')
            ->leftJoin('commentaire.auteur', 'auteur')->addSelect('auteur')
            ->innerJoin('commentaire.jeu', 'jeu')->addSelect('jeu')
            ->andWhere('jeu.statut = :statut')->setParameter('statut', StatutJeu::Approuve)
            ->orderBy('commentaire.dateCommentaire', 'DESC')
            ->setMaxResults(max(1, min(20, $limite)))
            ->getQuery()->getResult();
    }
}
