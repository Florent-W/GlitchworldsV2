<?php

namespace App\Repository;

use App\Entity\Jeu;
use App\Enum\StatutJeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Jeu>
 */
class JeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jeu::class);
    }

    /**
     * @return array{jeux: list<Jeu>, total: int, page: int, pages: int, parPage: int, recherche: string, categorie: string}
     */
    public function trouverApprouvesPagines(int $page = 1, int $parPage = 20, string $recherche = '', string $categorie = ''): array
    {
        $page = max(1, $page);
        $parPage = max(1, min(50, $parPage));
        $recherche = trim($recherche);
        $categorie = trim($categorie);

        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.categorie', 'c')
            ->addSelect('c')
            ->andWhere('j.statut = :statut')
            ->setParameter('statut', StatutJeu::Approuve);

        if ($recherche !== '') {
            $qb
                ->andWhere('j.nom LIKE :recherche OR j.description LIKE :recherche OR j.slug LIKE :recherche OR j.developpeur LIKE :recherche')
                ->setParameter('recherche', '%'.$recherche.'%');
        }

        if ($categorie !== '') {
            $qb
                ->andWhere('c.slug = :categorie')
                ->setParameter('categorie', $categorie);
        }

        $total = (clone $qb)
            ->select('COUNT(j.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, ceil($total / $parPage));
        if ($page > $pages) {
            $page = $pages;
        }

        /** @var list<Jeu> $jeux */
        $jeux = $qb
            ->orderBy('j.dateSortie', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->setFirstResult(($page - 1) * $parPage)
            ->setMaxResults($parPage)
            ->getQuery()
            ->getResult();

        return [
            'jeux' => $jeux,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'parPage' => $parPage,
            'recherche' => $recherche,
            'categorie' => $categorie,
        ];
    }
}
