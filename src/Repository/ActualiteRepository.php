<?php

namespace App\Repository;

use App\Entity\Actualite;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use App\Entity\Jeu;
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
    public function trouverPubliees(int $page, int $limite, ?CategorieActualite $categorie, string $recherche = ''): array
    {
        $page = max(1, $page);
        $requete = $this->createQueryBuilder('actualite')
            ->leftJoin('actualite.auteur', 'auteur')->addSelect('auteur')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->orderBy('actualite.publieeLe', 'DESC');

        if ($categorie !== null) {
            $requete->andWhere('actualite.categorie = :categorie')->setParameter('categorie', $categorie);
        }
        if ('' !== $recherche) {
            $requete
                ->andWhere('(LOWER(actualite.titre) LIKE :recherche OR LOWER(actualite.description) LIKE :recherche)')
                ->setParameter('recherche', '%'.mb_strtolower($recherche).'%');
        }

        $pagination = new Paginator($requete->getQuery()->setFirstResult(($page - 1) * $limite)->setMaxResults($limite));
        $total = \count($pagination);
        $pages = max(1, (int) ceil($total / $limite));

        return ['actualites' => iterator_to_array($pagination), 'page' => min($page, $pages), 'pages' => $pages, 'total' => $total];
    }

    /** @return list<Actualite> */
    public function trouverPourJeu(Jeu $jeu, int $limite = 4): array
    {
        return $this->createQueryBuilder('actualite')
            ->innerJoin('actualite.jeux', 'jeu')
            ->andWhere('jeu = :jeu')->setParameter('jeu', $jeu)
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->addOrderBy('actualite.miseEnAvant', 'DESC')
            ->addOrderBy('actualite.publieeLe', 'DESC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }

    /** @return list<Actualite> */
    public function trouverDernieres(int $limite = 4): array
    {
        return $this->createQueryBuilder('actualite')
            ->leftJoin('actualite.auteur', 'auteur')->addSelect('auteur')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->orderBy('actualite.publieeLe', 'DESC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }

    /** @return list<Actualite> */
    public function trouverMisesEnAvant(int $limite = 5): array
    {
        return $this->createQueryBuilder('actualite')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->andWhere('actualite.miseEnAvant = true')
            ->orderBy('actualite.publieeLe', 'DESC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }

    /** @return list<Actualite> */
    public function trouverPourSitemap(): array
    {
        return $this->createQueryBuilder('actualite')
            ->select('partial actualite.{id, slug, publieeLe}')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->orderBy('actualite.publieeLe', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return list<Actualite> */
    public function rechercherPourApercu(string $recherche, int $limite = 6): array
    {
        return $this->createQueryBuilder('actualite')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->andWhere('(LOWER(actualite.titre) LIKE :recherche OR LOWER(actualite.description) LIKE :recherche)')
            ->setParameter('recherche', '%'.mb_strtolower(trim($recherche)).'%')
            ->orderBy('actualite.publieeLe', 'DESC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }

    public function compterPourApercu(string $recherche): int
    {
        return (int) $this->createQueryBuilder('actualite')
            ->select('COUNT(actualite.id)')
            ->andWhere('actualite.statut = :statut')->setParameter('statut', StatutActualite::Publiee)
            ->andWhere('(LOWER(actualite.titre) LIKE :recherche OR LOWER(actualite.description) LIKE :recherche)')
            ->setParameter('recherche', '%'.mb_strtolower(trim($recherche)).'%')
            ->getQuery()->getSingleScalarResult();
    }
}
