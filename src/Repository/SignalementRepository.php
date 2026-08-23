<?php

namespace App\Repository;

use App\Entity\Signalement;
use App\Enum\StatutSignalement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class SignalementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Signalement::class); }

    /** @return list<Signalement> */
    public function trouverPourModeration(?StatutSignalement $statut = null): array
    {
        $requete = $this->createQueryBuilder('s')->addSelect('a', 'm', 'j', 'cj', 'ca', 'p', 'profil', 'avis', 'message')->leftJoin('s.signalePar', 'a')->leftJoin('s.traitePar', 'm')->leftJoin('s.jeu', 'j')->leftJoin('s.commentaireJeu', 'cj')->leftJoin('s.commentaireActualite', 'ca')->leftJoin('s.publication', 'p')->leftJoin('s.profil', 'profil')->leftJoin('s.avis', 'avis')->leftJoin('s.message', 'message')->orderBy('s.signaleLe', 'DESC');
        if ($statut) { $requete->andWhere('s.statut = :statut')->setParameter('statut', $statut); }
        return $requete->getQuery()->getResult();
    }
}
