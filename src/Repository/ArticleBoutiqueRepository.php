<?php

namespace App\Repository;

use App\Entity\ArticleBoutique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ArticleBoutiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ArticleBoutique::class); }
    /** @return list<ArticleBoutique> */
    public function trouverActifs(): array { return $this->findBy(['actif' => true], ['prix' => 'ASC', 'nom' => 'ASC']); }
}
