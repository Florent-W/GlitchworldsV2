<?php

namespace App\Repository;

use App\Entity\ListeJeux;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<ListeJeux>
 */
final class ListeJeuxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeJeux::class);
    }

    /** @return list<ListeJeux> */
    public function trouverPour(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('elements', 'jeux', 'categorie', 'genres')
            ->leftJoin('l.elements', 'elements')
            ->leftJoin('elements.jeu', 'jeux')
            ->leftJoin('jeux.categorie', 'categorie')
            ->leftJoin('jeux.genres', 'genres')
            ->andWhere('l.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('l.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<ListeJeux> */
    public function trouverPubliquesPourSitemap(): array
    {
        return $this->createQueryBuilder('liste')
            ->andWhere('liste.publique = true')
            ->andWhere('liste.slug IS NOT NULL')
            ->orderBy('liste.modifieLe', 'DESC')
            ->getQuery()->getResult();
    }

    /** @return array{listes: list<ListeJeux>, total: int, page: int, pages: int} */
    public function trouverPubliquesPaginees(int $page, int $parPage = 12, string $recherche = ''): array
    {
        $page = max(1, $page);
        $constructeur = $this->createQueryBuilder('liste')
            ->addSelect('utilisateur', 'elements', 'jeu')
            ->join('liste.utilisateur', 'utilisateur')
            ->leftJoin('liste.elements', 'elements')
            ->leftJoin('elements.jeu', 'jeu')
            ->andWhere('liste.publique = true')
            ->andWhere('liste.slug IS NOT NULL')
            ->orderBy('liste.modifieLe', 'DESC')
            ->addOrderBy('liste.id', 'DESC');
        $recherche = trim(mb_substr($recherche, 0, 80));
        if ($recherche !== '') {
            $constructeur
                ->andWhere('liste.nom LIKE :recherche OR liste.description LIKE :recherche OR utilisateur.pseudo LIKE :recherche OR jeu.nom LIKE :recherche')
                ->setParameter('recherche', '%'.$recherche.'%');
        }
        $requete = $constructeur->getQuery();
        $pagination = new Paginator($requete);
        $total = count($pagination);
        $pages = max(1, (int) ceil($total / $parPage));
        $page = min($page, $pages);
        $requete->setFirstResult(($page - 1) * $parPage)->setMaxResults($parPage);

        return [
            'listes' => iterator_to_array($pagination->getIterator(), false),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}
