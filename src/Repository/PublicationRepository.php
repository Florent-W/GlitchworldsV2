<?php

namespace App\Repository;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Publication> */
final class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /** @return list<Publication> */
    public function trouverDernieres(int $limite = 20): array
    {
        return $this->createQueryBuilder('publication')
            ->leftJoin('publication.auteur', 'auteur')->addSelect('auteur')
            ->leftJoin('publication.aimePar', 'aimePar')->addSelect('aimePar')
            ->leftJoin('publication.reponses', 'reponses')->addSelect('reponses')
            ->leftJoin('reponses.auteur', 'auteurReponse')->addSelect('auteurReponse')
            ->leftJoin('publication.votes', 'votes')->addSelect('votes')
            ->orderBy('publication.publieeLe', 'DESC')
            ->setMaxResults(max(1, min(50, $limite)))
            ->getQuery()->getResult();
    }

    /** @return list<Publication> */
    public function trouverPourAuteur(Utilisateur $auteur, int $limite = 20): array
    {
        return $this->createQueryBuilder('publication')
            ->leftJoin('publication.aimePar', 'aimePar')->addSelect('aimePar')
            ->andWhere('publication.auteur = :auteur')->setParameter('auteur', $auteur)
            ->orderBy('publication.publieeLe', 'DESC')
            ->setMaxResults(max(1, min(50, $limite)))
            ->getQuery()->getResult();
    }
}
