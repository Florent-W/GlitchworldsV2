<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Message> */
final class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Message::class); }

    public function compterNonLus(Utilisateur $utilisateur, ?Conversation $conversation = null): int
    {
        $requete = $this->createQueryBuilder('message')->select('COUNT(message.id)')
            ->innerJoin('message.conversation', 'conversation')
            ->andWhere('(conversation.membreA = :utilisateur OR conversation.membreB = :utilisateur)')
            ->andWhere('message.auteur != :utilisateur')->andWhere('message.luLe IS NULL')
            ->setParameter('utilisateur', $utilisateur);
        if ($conversation) { $requete->andWhere('message.conversation = :conversation')->setParameter('conversation', $conversation); }

        return (int) $requete->getQuery()->getSingleScalarResult();
    }

    public function marquerCommeLus(Conversation $conversation, Utilisateur $utilisateur): void
    {
        $this->createQueryBuilder('message')->update()
            ->set('message.luLe', ':maintenant')
            ->andWhere('message.conversation = :conversation')->andWhere('message.auteur != :utilisateur')->andWhere('message.luLe IS NULL')
            ->setParameter('maintenant', new \DateTimeImmutable())->setParameter('conversation', $conversation)->setParameter('utilisateur', $utilisateur)
            ->getQuery()->execute();
    }
}
