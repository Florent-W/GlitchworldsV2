<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Conversation> */
final class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Conversation::class); }

    /** @return list<Conversation> */
    public function trouverPour(Utilisateur $utilisateur, string $recherche = '', bool $archivees = false): array
    {
        return $this->createQueryBuilder('conversation')
            ->leftJoin('conversation.membreA', 'membreA')->addSelect('membreA')
            ->leftJoin('conversation.membreB', 'membreB')->addSelect('membreB')
            ->andWhere('conversation.membreA = :utilisateur OR conversation.membreB = :utilisateur')->setParameter('utilisateur', $utilisateur)
            ->andWhere('(conversation.membreA = :utilisateur AND conversation.archiveeParA = :archivees) OR (conversation.membreB = :utilisateur AND conversation.archiveeParB = :archivees)')
            ->setParameter('archivees', $archivees)
            ->andWhere(':recherche = \'\' OR (conversation.membreA != :utilisateur AND LOWER(membreA.pseudo) LIKE :mot) OR (conversation.membreB != :utilisateur AND LOWER(membreB.pseudo) LIKE :mot)')
            ->setParameter('recherche', trim($recherche))->setParameter('mot', '%'.mb_strtolower(trim($recherche)).'%')
            ->orderBy('conversation.miseAJourLe', 'DESC')->setMaxResults(50)
            ->getQuery()->getResult();
    }

    public function trouverEntre(Utilisateur $a, Utilisateur $b): ?Conversation
    {
        return $this->createQueryBuilder('conversation')
            ->andWhere('(conversation.membreA = :a AND conversation.membreB = :b) OR (conversation.membreA = :b AND conversation.membreB = :a)')
            ->setParameter('a', $a)->setParameter('b', $b)->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
