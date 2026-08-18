<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /** @return list<Utilisateur> */
    public function rechercherPourAdministration(string $recherche): array
    {
        $requete = $this->createQueryBuilder('utilisateur')
            ->orderBy('utilisateur.inscritLe', 'DESC')
            ->setMaxResults(100);

        if ($recherche !== '') {
            $requete
                ->andWhere('LOWER(utilisateur.pseudo) LIKE :recherche OR LOWER(utilisateur.email) LIKE :recherche')
                ->setParameter('recherche', '%'.mb_strtolower($recherche).'%');
        }

        return $requete->getQuery()->getResult();
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        $utilisateurs = $this->createQueryBuilder('utilisateur')
            ->andWhere('LOWER(utilisateur.email) = :identifiant OR LOWER(utilisateur.pseudo) = :identifiant')
            ->setParameter('identifiant', mb_strtolower(trim($identifier)))
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        // Un ancien pseudo peut être dupliqué : dans ce cas, aucun compte
        // n'est choisi arbitrairement.
        return 1 === \count($utilisateurs) ? $utilisateurs[0] : null;
    }

    /** @return list<Utilisateur> */
    public function rechercherParPseudo(string $recherche, int $limite = 5): array
    {
        return $this->createQueryBuilder('utilisateur')
            ->andWhere('LOWER(utilisateur.pseudo) LIKE :recherche')
            ->setParameter('recherche', '%'.mb_strtolower(trim($recherche)).'%')
            ->orderBy('utilisateur.pseudo', 'ASC')
            ->setMaxResults(max(1, min(10, $limite)))
            ->getQuery()->getResult();
    }

    /** @return list<Utilisateur> */
    public function trouverEnLigne(int $limite = 8): array
    {
        return $this->createQueryBuilder('utilisateur')
            ->andWhere('utilisateur.derniereActivite >= :limite')
            ->setParameter('limite', new \DateTimeImmutable('-5 minutes'))
            ->orderBy('utilisateur.derniereActivite', 'DESC')
            ->setMaxResults(max(1, min(20, $limite)))
            ->getQuery()->getResult();
    }

    /** @return list<Utilisateur> */
    public function trouverClassement(int $limite = 100): array
    {
        return $this->createQueryBuilder('utilisateur')
            ->orderBy('utilisateur.experience', 'DESC')
            ->addOrderBy('utilisateur.points', 'DESC')
            ->addOrderBy('utilisateur.inscritLe', 'ASC')
            ->setMaxResults(max(1, min(100, $limite)))
            ->getQuery()->getResult();
    }
}
