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
    public function trouverRecents(Actualite $actualite, int $limite = 10, int $decalage = 0): array
    {
        $identifiants = $this->createQueryBuilder('racine')
            ->select('racine.id')
            ->andWhere('racine.actualite = :actualite')
            ->andWhere('racine.parent IS NULL')
            ->setParameter('actualite', $actualite)
            ->orderBy('racine.dateCommentaire', 'DESC')
            ->setFirstResult(max(0, $decalage))
            ->setMaxResults($limite)
            ->getQuery()->getSingleColumnResult();

        if ([] === $identifiants) {
            return [];
        }

        return $this->createQueryBuilder('commentaire')
            ->leftJoin('commentaire.auteur', 'auteur')->addSelect('auteur')
            ->leftJoin('commentaire.aimePar', 'aimePar')->addSelect('aimePar')
            ->leftJoin('commentaire.reponses', 'reponse')->addSelect('reponse')
            ->leftJoin('reponse.auteur', 'auteurReponse')->addSelect('auteurReponse')
            ->leftJoin('reponse.aimePar', 'aimeParReponse')->addSelect('aimeParReponse')
            ->andWhere('commentaire.id IN (:identifiants)')->setParameter('identifiants', $identifiants)
            ->orderBy('commentaire.dateCommentaire', 'DESC')
            ->addOrderBy('reponse.dateCommentaire', 'ASC')
            ->getQuery()->getResult();
    }

    public function compterRacinesPourActualite(Actualite $actualite): int
    {
        return (int) $this->createQueryBuilder('commentaire')
            ->select('COUNT(commentaire.id)')
            ->andWhere('commentaire.actualite = :actualite')
            ->andWhere('commentaire.parent IS NULL')
            ->setParameter('actualite', $actualite)
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * @param list<Actualite> $actualites
     * @return array<int, int>
     */
    public function compterParActualites(array $actualites): array
    {
        if ([] === $actualites) {
            return [];
        }

        $lignes = $this->createQueryBuilder('commentaire')
            ->select('IDENTITY(commentaire.actualite) AS actualiteId, COUNT(commentaire.id) AS total')
            ->andWhere('commentaire.actualite IN (:actualites)')
            ->setParameter('actualites', $actualites)
            ->groupBy('commentaire.actualite')
            ->getQuery()
            ->getArrayResult();

        $compteurs = [];
        foreach ($lignes as $ligne) {
            $compteurs[(int) $ligne['actualiteId']] = (int) $ligne['total'];
        }

        return $compteurs;
    }

    /** @return list<CommentaireActualite> */
    public function trouverPourModeration(int $limite = 50): array
    {
        return $this->createQueryBuilder('commentaire')
            ->leftJoin('commentaire.auteur', 'auteur')->addSelect('auteur')
            ->leftJoin('commentaire.parent', 'parent')->addSelect('parent')
            ->innerJoin('commentaire.actualite', 'actualite')->addSelect('actualite')
            ->orderBy('commentaire.dateCommentaire', 'DESC')
            ->setMaxResults(max(1, min(100, $limite)))
            ->getQuery()->getResult();
    }
}
