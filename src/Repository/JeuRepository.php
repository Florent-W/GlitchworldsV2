<?php

namespace App\Repository;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Enum\TriJeu;
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
     * @return array{jeux: list<Jeu>, total: int, page: int, pages: int, parPage: int, recherche: string, categorie: string, plateforme: string, genre: string, langue: string, tri: TriJeu}
     */
    public function trouverApprouvesPagines(
        int $page = 1,
        int $parPage = 20,
        string $recherche = '',
        string $categorie = '',
        string $plateforme = '',
        string $genre = '',
        string $langue = '',
        TriJeu $tri = TriJeu::Recent,
    ): array {
        $page = max(1, $page);
        $parPage = max(1, min(50, $parPage));
        $recherche = trim($recherche);
        $categorie = trim($categorie);
        $plateforme = trim($plateforme);
        $genre = trim($genre);
        $langue = trim($langue);

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

        if ($plateforme !== '') {
            $qb
                ->innerJoin('j.plateformes', 'p')
                ->andWhere('p.slug = :plateforme')
                ->setParameter('plateforme', $plateforme);
        }

        if ($genre !== '') {
            $qb
                ->innerJoin('j.genres', 'g')
                ->andWhere('g.slug = :genre')
                ->setParameter('genre', $genre);
        }

        if ($langue !== '') {
            $qb
                ->innerJoin('j.langues', 'l')
                ->andWhere('l.slug = :langue')
                ->setParameter('langue', $langue);
        }

        $total = (clone $qb)
            ->select('COUNT(DISTINCT j.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, ceil($total / $parPage));
        if ($page > $pages) {
            $page = $pages;
        }

        $qb->distinct();

        match ($tri) {
            TriJeu::Recent => $qb->orderBy('j.dateSortie', 'DESC')->addOrderBy('j.id', 'DESC'),
            TriJeu::Nom => $qb->orderBy('j.nom', 'ASC'),
            TriJeu::Ancien => $qb->orderBy('j.dateSortie', 'ASC')->addOrderBy('j.id', 'ASC'),
        };

        /** @var list<Jeu> $jeux */
        $jeux = $qb
            ->setFirstResult(($page - 1) * $parPage)
            ->setMaxResults($parPage)
            ->getQuery()
            ->getResult();

        if ($jeux !== []) {
            $ids = array_map(static fn (Jeu $jeu) => $jeu->getId(), $jeux);

            $this->createQueryBuilder('details')
                ->leftJoin('details.plateformes', 'plateforme')
                ->addSelect('plateforme')
                ->leftJoin('details.genres', 'genre')
                ->addSelect('genre')
                ->leftJoin('details.langues', 'langue')
                ->addSelect('langue')
                ->andWhere('details.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
        }

        return [
            'jeux' => $jeux,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'parPage' => $parPage,
            'recherche' => $recherche,
            'categorie' => $categorie,
            'plateforme' => $plateforme,
            'genre' => $genre,
            'langue' => $langue,
            'tri' => $tri,
        ];
    }

    /**
     * @return list<Jeu>
     */
    public function trouverSimilaires(Jeu $jeu, int $limite = 4): array
    {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.id != :jeu')
            ->andWhere('j.statut = :statut')
            ->setParameter('jeu', $jeu->getId())
            ->setParameter('statut', StatutJeu::Approuve)
            ->orderBy('j.dateSortie', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->setMaxResults($limite);

        if ($jeu->getCategorie() !== null) {
            $qb
                ->andWhere('j.categorie = :categorie')
                ->setParameter('categorie', $jeu->getCategorie());
        }

        return $qb->getQuery()->getResult();
    }

    /** @return array{jeux: list<Jeu>, total: int, page: int, pages: int} */
    public function trouverFavorisPagines(Utilisateur $utilisateur, int $page = 1, int $parPage = 12): array
    {
        $page = max(1, $page);
        $parPage = max(1, min(50, $parPage));
        $qb = $this->createQueryBuilder('j')
            ->innerJoin('j.ajouteAuxFavorisPar', 'u')
            ->leftJoin('j.categorie', 'categorie')
            ->addSelect('categorie')
            ->andWhere('u = :utilisateur')
            ->andWhere('j.statut = :statut')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', StatutJeu::Approuve);

        $total = (int) (clone $qb)->select('COUNT(j.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min($page, $pages);

        /** @var list<Jeu> $jeux */
        $jeux = $qb
            ->orderBy('j.nom', 'ASC')
            ->setFirstResult(($page - 1) * $parPage)
            ->setMaxResults($parPage)
            ->getQuery()
            ->getResult();

        if ($jeux !== []) {
            $this->createQueryBuilder('details')
                ->leftJoin('details.genres', 'genre')->addSelect('genre')
                ->leftJoin('details.plateformes', 'plateforme')->addSelect('plateforme')
                ->andWhere('details.id IN (:ids)')
                ->setParameter('ids', array_map(static fn (Jeu $jeu) => $jeu->getId(), $jeux))
                ->getQuery()
                ->getResult();
        }

        return compact('jeux', 'total', 'page', 'pages');
    }

    /** @return list<Jeu> */
    public function trouverNouveautes(int $limite = 6): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.categorie', 'categorie')->addSelect('categorie')
            ->andWhere('j.statut = :statut')
            ->setParameter('statut', StatutJeu::Approuve)
            ->orderBy('j.creeLe', 'DESC')
            ->addOrderBy('j.id', 'DESC')
            ->setMaxResults(max(1, min(12, $limite)))
            ->getQuery()
            ->getResult();
    }

    /** @return list<Jeu> */
    public function trouverPopulaires(int $limite = 6): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.categorie', 'categorie')->addSelect('categorie')
            ->innerJoin('App\Entity\Avis', 'avis', 'WITH', 'avis.jeu = j')
            ->addSelect('AVG(avis.note) AS HIDDEN moyenneNote')
            ->addSelect('COUNT(avis.id) AS HIDDEN nombreAvis')
            ->andWhere('j.statut = :statut')
            ->setParameter('statut', StatutJeu::Approuve)
            ->groupBy('j.id, categorie.id')
            ->orderBy('moyenneNote', 'DESC')
            ->addOrderBy('nombreAvis', 'DESC')
            ->setMaxResults(max(1, min(12, $limite)))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Jeu>
     */
    public function trouverPourSitemap(): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.statut = :statut')
            ->setParameter('statut', StatutJeu::Approuve)
            ->orderBy('j.modifieLe', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
