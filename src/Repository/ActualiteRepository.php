<?php

namespace App\Repository;

use App\Entity\Actualite;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Actualite> */
final class ActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actualite::class);
    }

    /** @return array{actualites: list<Actualite>, page: int, pages: int, total: int} */
    public function trouverPubliees(int $page, int $limite, ?CategorieActualite $categorie): array
    {
        $page = max(1, $page);
        $requete = $this->createQueryBuilder('actualite')
            ->leftJoin('actualite.auteur', 'auteur')->addSelect('auteur')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->orderBy('actualite.publieeLe', 'DESC');

        if ($categorie !== null) {
            $requete->andWhere('actualite.categorie = :categorie')->setParameter('categorie', $categorie);
        }

        $pagination = new Paginator($requete->getQuery()->setFirstResult(($page - 1) * $limite)->setMaxResults($limite));
        $total = \count($pagination);
        $pages = max(1, (int) ceil($total / $limite));

        return ['actualites' => iterator_to_array($pagination), 'page' => min($page, $pages), 'pages' => $pages, 'total' => $total];
    }
}
