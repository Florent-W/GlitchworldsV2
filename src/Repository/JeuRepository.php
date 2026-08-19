<?php

namespace App\Repository;

use App\Entity\Avis;
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
     * @return array{
     *     jeux: list<Jeu>,
     *     total: int,
     *     page: int,
     *     pages: int,
     *     parPage: int,
     *     recherche: string,
     *     categorie: string,
     *     plateforme: string,
     *     genre: string,
     *     langue: string,
     *     annee: ?int,
     *     mesFavoris: bool,
     *     tri: TriJeu
     * }
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
        ?int $annee = null,
        bool $mesFavoris = false,
        ?Utilisateur $utilisateur = null,
    ): array {
        $page = max(1, $page);
        $parPage = max(1, min(50, $parPage));
        $recherche = trim($recherche);
        $categorie = trim($categorie);
        $plateforme = trim($plateforme);
        $genre = trim($genre);
        $langue = trim($langue);
        $mesFavoris = $mesFavoris && $utilisateur instanceof Utilisateur;

        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.categorie', 'c')
            ->addSelect('c')
            ->andWhere('j.statut = :statut')
            ->setParameter('statut', StatutJeu::Approuve);

        if ($recherche !== '') {
            $qb
                ->andWhere('(j.nom LIKE :recherche OR j.description LIKE :recherche OR j.slug LIKE :recherche OR j.developpeur LIKE :recherche)')
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

        if ($annee !== null) {
            $qb
                ->andWhere('j.dateSortie >= :anneeDebut AND j.dateSortie < :anneeFin')
                ->setParameter('anneeDebut', new \DateTimeImmutable(sprintf('%04d-01-01', $annee)))
                ->setParameter('anneeFin', new \DateTimeImmutable(sprintf('%04d-01-01', $annee + 1)));
        }

        if ($mesFavoris) {
            $qb
                ->innerJoin('j.ajouteAuxFavorisPar', 'favori')
                ->andWhere('favori = :membreFavoris')
                ->setParameter('membreFavoris', $utilisateur);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT j.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / $parPage));
        if ($page > $pages) {
            $page = $pages;
        }

        match ($tri) {
            TriJeu::Recent => $qb->distinct()->orderBy('j.dateSortie', 'DESC')->addOrderBy('j.id', 'DESC'),
            TriJeu::Nom => $qb->distinct()->orderBy('j.nom', 'ASC'),
            TriJeu::Ancien => $qb->distinct()->orderBy('j.dateSortie', 'ASC')->addOrderBy('j.id', 'ASC'),
            TriJeu::Populaire => $qb
                ->leftJoin('j.ajouteAuxFavorisPar', 'triFavori')
                ->addSelect('COUNT(DISTINCT triFavori.id) AS HIDDEN nbFavoris')
                ->groupBy('j.id')
                ->addGroupBy('c.id')
                ->orderBy('nbFavoris', 'DESC')
                ->addOrderBy('j.nom', 'ASC'),
            TriJeu::Note => $qb
                ->leftJoin(Avis::class, 'triAvis', 'WITH', 'triAvis.jeu = j')
                ->addSelect('AVG(triAvis.note) AS HIDDEN noteMoyenne')
                ->groupBy('j.id')
                ->addGroupBy('c.id')
                ->orderBy('noteMoyenne', 'DESC')
                ->addOrderBy('j.nom', 'ASC'),
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
            'annee' => $annee,
            'mesFavoris' => $mesFavoris,
            'tri' => $tri,
        ];
    }

    /** @return list<int> */
    public function listerAnneesSortie(): array
    {
        $annees = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT YEAR(date_sortie) AS annee
             FROM jeu
             WHERE statut = :statut AND date_sortie IS NOT NULL
             ORDER BY annee DESC',
            ['statut' => StatutJeu::Approuve->value]
        );

        return array_values(array_filter(array_map(static fn (mixed $annee): int => (int) $annee, $annees)));
    }

    /** @return list<Jeu> */
    public function rechercherPourApercu(string $recherche, int $limite = 6): array
    {
        return $this->createQueryBuilder('jeu')
            ->leftJoin('jeu.categorie', 'categorie')->addSelect('categorie')
            ->andWhere('jeu.statut = :statut')->setParameter('statut', StatutJeu::Approuve)
            ->andWhere('(jeu.nom LIKE :recherche OR jeu.description LIKE :recherche OR jeu.slug LIKE :recherche OR jeu.developpeur LIKE :recherche)')
            ->setParameter('recherche', '%'.trim($recherche).'%')
            ->orderBy('jeu.nom', 'ASC')->setMaxResults($limite)
            ->getQuery()->getResult();
    }

    public function compterPourApercu(string $recherche): int
    {
        return (int) $this->createQueryBuilder('jeu')
            ->select('COUNT(jeu.id)')
            ->andWhere('jeu.statut = :statut')->setParameter('statut', StatutJeu::Approuve)
            ->andWhere('(jeu.nom LIKE :recherche OR jeu.description LIKE :recherche OR jeu.slug LIKE :recherche OR jeu.developpeur LIKE :recherche)')
            ->setParameter('recherche', '%'.trim($recherche).'%')
            ->getQuery()->getSingleScalarResult();
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

    /** @return list<Jeu> */
    public function trouverPropositions(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.createur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('j.creeLe', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Jeu> */
    public function trouverEnAttente(): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.createur', 'createur')->addSelect('createur')
            ->leftJoin('j.categorie', 'categorie')->addSelect('categorie')
            ->andWhere('j.statut = :statut')
            ->setParameter('statut', StatutJeu::EnAttente)
            ->orderBy('j.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return array{jeux: list<Jeu>, total: int, page: int, pages: int} */
    public function trouverPourAdministration(string $recherche, ?StatutJeu $statut, int $page, int $parPage = 20): array
    {
        $page = max(1, $page);
        $qb = $this->createQueryBuilder('j')->leftJoin('j.createur', 'createur')->addSelect('createur')->leftJoin('j.categorie', 'categorie')->addSelect('categorie');
        if ($recherche !== '') { $qb->andWhere('(j.nom LIKE :recherche OR j.slug LIKE :recherche OR j.developpeur LIKE :recherche)')->setParameter('recherche', '%'.$recherche.'%'); }
        if ($statut) { $qb->andWhere('j.statut = :statut')->setParameter('statut', $statut); }
        $total = (int) (clone $qb)->select('COUNT(j.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min($page, $pages);
        $jeux = $qb->orderBy('j.modifieLe', 'DESC')->setFirstResult(($page - 1) * $parPage)->setMaxResults($parPage)->getQuery()->getResult();
        return compact('jeux', 'total', 'page', 'pages');
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

    /** @return list<Jeu> */
    public function trouverApprouvesPar(Utilisateur $utilisateur, int $limite = 20): array
    {
        return $this->createQueryBuilder('j')
            ->leftJoin('j.categorie', 'categorie')->addSelect('categorie')
            ->andWhere('j.createur = :utilisateur')->setParameter('utilisateur', $utilisateur)
            ->andWhere('j.statut = :statut')->setParameter('statut', StatutJeu::Approuve)
            ->orderBy('j.creeLe', 'DESC')->setMaxResults(max(1, min(50, $limite)))
            ->getQuery()->getResult();
    }
}
