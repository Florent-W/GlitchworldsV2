<?php

namespace App\Service;

use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Enum\CategorieActualite;
use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class ContenuAccueil
{
    public function __construct(
        private JeuRepository $jeux,
        private ActualiteRepository $actualites,
        private CacheInterface $cache,
    ) {
    }

    /** @return list<Jeu> */
    public function nouveautes(): array
    {
        $ids = $this->cache->get('glitchworlds.accueil.nouveautes.v1', function (ItemInterface $item): array {
            $item->expiresAfter(300);

            return array_map(static fn (Jeu $jeu): int => (int) $jeu->getId(), $this->jeux->trouverNouveautes(9));
        });

        return $this->ordonner($this->jeux->trouverParIdentifiants($ids), $ids);
    }

    /** @return list<Jeu> */
    public function populaires(): array
    {
        $ids = $this->cache->get('glitchworlds.accueil.populaires.v1', function (ItemInterface $item): array {
            $item->expiresAfter(900);

            return array_map(static fn (Jeu $jeu): int => (int) $jeu->getId(), $this->jeux->trouverPopulaires(9));
        });

        return $this->ordonner($this->jeux->trouverParIdentifiants($ids), $ids);
    }

    /** @return list<Actualite> */
    public function dernieresActualites(): array
    {
        return $this->actualitesCachees('actualites', null);
    }

    /** @return list<Actualite> */
    public function derniersGlitchs(): array
    {
        return $this->actualitesCachees('glitchs', CategorieActualite::Glitchs);
    }

    /** @return list<Actualite> */
    public function misesEnAvant(): array
    {
        $ids = $this->cache->get('glitchworlds.accueil.mises_en_avant.v1', function (ItemInterface $item): array {
            $item->expiresAfter(300);

            return array_map(
                static fn (Actualite $actualite): int => (int) $actualite->getId(),
                $this->actualites->trouverMisesEnAvant(),
            );
        });

        return $this->ordonner($this->actualites->trouverParIdentifiants($ids), $ids);
    }

    /** @return list<Actualite> */
    private function actualitesCachees(string $cle, ?CategorieActualite $categorie): array
    {
        $ids = $this->cache->get('glitchworlds.accueil.'.$cle.'.v1', function (ItemInterface $item) use ($categorie): array {
            $item->expiresAfter(300);

            return array_map(
                static fn (Actualite $actualite): int => (int) $actualite->getId(),
                $this->actualites->trouverDernieres(9, $categorie),
            );
        });

        return $this->ordonner($this->actualites->trouverParIdentifiants($ids), $ids);
    }

    /**
     * @template T of object
     * @param list<T> $entites
     * @param list<int> $identifiants
     * @return list<T>
     */
    private function ordonner(array $entites, array $identifiants): array
    {
        $positions = array_flip($identifiants);
        usort(
            $entites,
            static fn (object $a, object $b): int => ($positions[$a->getId()] ?? PHP_INT_MAX) <=> ($positions[$b->getId()] ?? PHP_INT_MAX),
        );

        return $entites;
    }
}
