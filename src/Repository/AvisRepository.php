<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Jeu;
use App\Enum\StatutJeu;
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
     * Résumé des notes pour un lot de jeux, en une seule requête (évite le N+1 sur les listes).
     *
     * @param iterable<Jeu> $jeux
     * @return array<int, array{moyenne: float, total: int}>
     */
    public function trouverResumesPour(iterable $jeux): array
    {
        $ids = [];
        foreach ($jeux as $jeu) {
            if ($jeu->getId() !== null) {
                $ids[] = $jeu->getId();
            }
        }

        if ($ids === []) {
            return [];
        }

        $lignes = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.jeu) AS jeuId', 'AVG(a.note) AS moyenne', 'COUNT(a.id) AS total')
            ->andWhere('a.jeu IN (:ids)')
            ->setParameter('ids', $ids)
            ->groupBy('a.jeu')
            ->getQuery()
            ->getResult();

        $resumes = [];
        foreach ($lignes as $ligne) {
            $resumes[(int) $ligne['jeuId']] = [
                'moyenne' => round((float) $ligne['moyenne'], 1),
                'total' => (int) $ligne['total'],
            ];
        }

        return $resumes;
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

    /** @return list<Avis> */
    public function trouverDernieresNotes(int $limite = 20): array
    {
        return $this->createQueryBuilder('avis')
            ->innerJoin('avis.auteur', 'auteur')->addSelect('auteur')
            ->innerJoin('avis.jeu', 'jeu')->addSelect('jeu')
            ->andWhere('jeu.statut = :statut')->setParameter('statut', StatutJeu::Approuve)
            ->orderBy('avis.dateAvis', 'DESC')
            ->setMaxResults(max(1, min(50, $limite)))
            ->getQuery()
            ->getResult();
    }

    /** @return list<Avis> */
    public function trouverAvisPourJeu(Jeu $jeu): array
    {
        return $this->createQueryBuilder('avis')
            ->leftJoin('avis.auteur', 'auteur')
            ->addSelect('auteur')
            ->andWhere('avis.jeu = :jeu')
            ->andWhere("TRIM(avis.contenu) <> ''")
            ->setParameter('jeu', $jeu)
            ->orderBy('avis.dateAvis', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
