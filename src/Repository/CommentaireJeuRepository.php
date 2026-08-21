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
        $identifiants = $this->createQueryBuilder('racine')
            ->select('racine.id')
            ->andWhere('racine.jeu = :jeu')
            ->andWhere('racine.parent IS NULL')
            ->setParameter('jeu', $jeu)
            ->orderBy('racine.dateCommentaire', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()->getSingleColumnResult();

        if ([] === $identifiants) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->leftJoin('c.auteur', 'auteur')
            ->addSelect('auteur')
            ->leftJoin('c.aimePar', 'aimePar')
            ->addSelect('aimePar')
            ->leftJoin('c.reponses', 'reponse')->addSelect('reponse')
            ->leftJoin('reponse.auteur', 'auteurReponse')->addSelect('auteurReponse')
            ->leftJoin('reponse.aimePar', 'aimeParReponse')->addSelect('aimeParReponse')
            ->andWhere('c.id IN (:identifiants)')
            ->setParameter('identifiants', $identifiants)
            ->orderBy('c.dateCommentaire', 'DESC')
            ->addOrderBy('reponse.dateCommentaire', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function compterPourJeu(Jeu $jeu): int
    {
        return $this->count(['jeu' => $jeu]);
    }

    /**
     * @param list<Jeu> $jeux
     * @return array<int, int>
     */
    public function compterParJeux(array $jeux): array
    {
        if ([] === $jeux) {
            return [];
        }

        $lignes = $this->createQueryBuilder('commentaire')
            ->select('IDENTITY(commentaire.jeu) AS jeuId, COUNT(commentaire.id) AS total')
            ->andWhere('commentaire.jeu IN (:jeux)')
            ->setParameter('jeux', $jeux)
            ->groupBy('commentaire.jeu')
            ->getQuery()
            ->getArrayResult();

        $compteurs = [];
        foreach ($lignes as $ligne) {
            $compteurs[(int) $ligne['jeuId']] = (int) $ligne['total'];
        }

        return $compteurs;
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

    public function compterPublics(): int
    {
        return (int) $this->createQueryBuilder('commentaire')->select('COUNT(commentaire.id)')
            ->innerJoin('commentaire.jeu', 'jeu')
            ->andWhere('jeu.statut = :statut')->setParameter('statut', StatutJeu::Approuve)
            ->getQuery()->getSingleScalarResult();
    }

    /** @return list<CommentaireJeu> */
    public function trouverPourModeration(int $limite = 50): array
    {
        return $this->createQueryBuilder('commentaire')
            ->leftJoin('commentaire.auteur', 'auteur')->addSelect('auteur')
            ->leftJoin('commentaire.parent', 'parent')->addSelect('parent')
            ->innerJoin('commentaire.jeu', 'jeu')->addSelect('jeu')
            ->orderBy('commentaire.dateCommentaire', 'DESC')
            ->setMaxResults(max(1, min(100, $limite)))
            ->getQuery()->getResult();
    }
}
