<?php

namespace App\Service;

use App\Entity\CommentaireActualite;
use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class StatistiquesPlateforme
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
    ) {
    }

    /**
     * @return array{jeux: int, membres: int, commentaires: int}
     */
    public function obtenir(): array
    {
        return $this->cache->get('glitchworlds.statistiques_plateforme.v1', function (ItemInterface $item): array {
            $item->expiresAfter(300);

            return [
                'jeux' => $this->entityManager->getRepository(Jeu::class)->count(['statut' => StatutJeu::Approuve]),
                'membres' => $this->entityManager->getRepository(Utilisateur::class)->count([]),
                'commentaires' => $this->entityManager->getRepository(CommentaireJeu::class)->count([])
                    + $this->entityManager->getRepository(CommentaireActualite::class)->count([]),
            ];
        });
    }
}
