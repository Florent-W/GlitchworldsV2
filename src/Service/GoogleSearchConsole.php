<?php

namespace App\Service;

use App\Entity\ConnexionSearchConsole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleSearchConsole
{
    private const PERIODES = [7, 28, 30, 90, 180, 365];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly ChiffrementJeton $chiffrement,
        #[Autowire(env: 'default::OAUTH_GOOGLE_ID')] private readonly ?string $clientId,
        #[Autowire(env: 'default::OAUTH_GOOGLE_SECRET')] private readonly ?string $clientSecret,
        #[Autowire(env: 'default::GOOGLE_SEARCH_CONSOLE_PROPERTY')] private readonly ?string $propriete,
        #[Autowire('%kernel.environment%')] private readonly string $environment,
    ) {}

    public function estConfiguree(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->propriete);
    }

    public function estConnectee(): bool
    {
        return $this->environment !== 'test' && $this->estConfiguree() && $this->connexion() !== null;
    }

    public function enregistrer(array $jetons): void
    {
        $connexion = $this->connexion() ?? new ConnexionSearchConsole();
        $connexion
            ->setJetonAcces($this->chiffrement->chiffrer((string) $jetons['access_token']))
            ->setExpireLe((new \DateTimeImmutable())->modify('+'.max(60, (int) ($jetons['expires_in'] ?? 3600)).' seconds'))
            ->setConnecteLe(new \DateTimeImmutable());
        if (!empty($jetons['refresh_token'])) {
            $connexion->setJetonRafraichissement($this->chiffrement->chiffrer((string) $jetons['refresh_token']));
        }
        $this->entityManager->persist($connexion);
        $this->entityManager->flush();
        $this->viderCache();
    }

    public function deconnecter(): void
    {
        if (($connexion = $this->connexion()) !== null) {
            $this->entityManager->remove($connexion);
            $this->entityManager->flush();
        }
        $this->viderCache();
    }

    public function statistiques(int $jours, string $contenu = 'tout'): ?array
    {
        if (!$this->estConfiguree() || !$this->estConnectee()) {
            return null;
        }
        $jours = in_array($jours, self::PERIODES, true) ? $jours : 30;
        $contenu = in_array($contenu, ['tout', 'jeux', 'actualites'], true) ? $contenu : 'tout';

        return $this->cache->get('search_console.performance.v5.'.$jours.'.'.$contenu, function (ItemInterface $item) use ($jours, $contenu): array {
            $item->expiresAfter(1800);
            $fin = new \DateTimeImmutable('-2 days');
            $debut = $fin->modify('-'.($jours - 1).' days');
            $finPrecedente = $debut->modify('-1 day');
            $debutPrecedente = $finPrecedente->modify('-'.($jours - 1).' days');
            $actuel = $this->interroger($debut, $fin, [], 1, $contenu);
            $precedent = $this->interroger($debutPrecedente, $finPrecedente, [], 1, $contenu);
            $ctrActuel = (float) ($actuel['ctr'] ?? 0) * 100;
            $ctrPrecedent = (float) ($precedent['ctr'] ?? 0) * 100;
            $positionActuelle = (float) ($actuel['position'] ?? 0);
            $positionPrecedente = (float) ($precedent['position'] ?? 0);
            $graphique = $this->graphique($debut, $fin, $this->interroger($debut, $fin, ['date'], $jours, $contenu)['rows'] ?? []);
            $requetes = $this->interroger($debut, $fin, ['query'], 250, $contenu)['rows'] ?? [];
            $pages = $this->interroger($debut, $fin, ['page'], 250, $contenu)['rows'] ?? [];
            $requetesPrecedentes = $this->interroger($debutPrecedente, $finPrecedente, ['query'], 250, $contenu)['rows'] ?? [];
            $pagesPrecedentes = $this->interroger($debutPrecedente, $finPrecedente, ['page'], 250, $contenu)['rows'] ?? [];
            $evolutionClics = $this->evolution((float) ($actuel['clicks'] ?? 0), (float) ($precedent['clicks'] ?? 0));
            $evolutionImpressions = $this->evolution((float) ($actuel['impressions'] ?? 0), (float) ($precedent['impressions'] ?? 0));

            return [
                'debut' => $debut,
                'fin' => $fin,
                'clics' => (int) round($actuel['clicks'] ?? 0),
                'impressions' => (int) round($actuel['impressions'] ?? 0),
                'ctr' => $ctrActuel,
                'position' => $positionActuelle,
                'evolutionClics' => $evolutionClics,
                'evolutionImpressions' => $evolutionImpressions,
                'variationCtr' => $ctrActuel - $ctrPrecedent,
                'gainPosition' => $positionActuelle > 0 && $positionPrecedente > 0 ? $positionPrecedente - $positionActuelle : 0,
                'graphique' => $graphique['points'],
                'maximumClics' => $graphique['maximumClics'],
                'maximumImpressions' => $graphique['maximumImpressions'],
                'requetes' => array_slice($requetes, 0, 8),
                'pages' => array_slice($pages, 0, 8),
                'appareils' => $this->appareils($this->interroger($debut, $fin, ['device'], 3, $contenu)['rows'] ?? []),
                'pays' => $this->pays($this->interroger($debut, $fin, ['country'], 5, $contenu)['rows'] ?? []),
                'alertes' => $this->alertes($evolutionClics, $evolutionImpressions),
                'insights' => [
                    'requetes' => $this->tendances($requetes, $requetesPrecedentes),
                    'pages' => $this->tendances($pages, $pagesPrecedentes),
                    'opportunites' => $this->opportunites($requetes, $ctrActuel),
                    'positions' => $this->evolutionPositions($pages, $pagesPrecedentes),
                    'prochesTop10' => $this->prochesTop10($requetes),
                ],
            ];
        });
    }

    private function interroger(\DateTimeImmutable $debut, \DateTimeImmutable $fin, array $dimensions = [], int $limite = 1, string $contenu = 'tout'): array
    {
        $reponse = $this->httpClient->request('POST', sprintf(
            'https://www.googleapis.com/webmasters/v3/sites/%s/searchAnalytics/query',
            rawurlencode((string) $this->propriete),
        ), [
            'auth_bearer' => $this->jetonAcces(),
            'json' => array_filter([
                'startDate' => $debut->format('Y-m-d'),
                'endDate' => $fin->format('Y-m-d'),
                'type' => 'web',
                'dimensions' => $dimensions,
                'rowLimit' => $limite,
                'dimensionFilterGroups' => $this->filtreContenu($contenu),
            ], static fn (mixed $valeur): bool => $valeur !== null),
        ])->toArray();

        return $dimensions === [] ? ($reponse['rows'][0] ?? []) : $reponse;
    }

    private function filtreContenu(string $contenu): ?array
    {
        $expression = match ($contenu) {
            'jeux' => '/jeu/',
            'actualites' => '/actualite/',
            default => null,
        };

        return $expression === null ? null : [[
            'groupType' => 'and',
            'filters' => [[
                'dimension' => 'page',
                'operator' => 'contains',
                'expression' => $expression,
            ]],
        ]];
    }

    private function jetonAcces(): string
    {
        $connexion = $this->connexion() ?? throw new \RuntimeException('Search Console n’est pas connecté.');
        if ($connexion->getExpireLe() > new \DateTimeImmutable('+60 seconds')) {
            return $this->chiffrement->dechiffrer($connexion->getJetonAcces());
        }
        if (!$connexion->getJetonRafraichissement()) {
            throw new \RuntimeException('Google demande une nouvelle autorisation Search Console.');
        }
        $jetons = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', ['body' => [
            'client_id' => (string) $this->clientId,
            'client_secret' => (string) $this->clientSecret,
            'refresh_token' => $this->chiffrement->dechiffrer($connexion->getJetonRafraichissement()),
            'grant_type' => 'refresh_token',
        ]])->toArray();
        $this->enregistrer($jetons);

        return (string) $jetons['access_token'];
    }

    private function connexion(): ?ConnexionSearchConsole
    {
        return $this->entityManager->getRepository(ConnexionSearchConsole::class)->findOneBy([], ['id' => 'DESC']);
    }

    private function evolution(float $actuel, float $precedent): int
    {
        return $precedent > 0 ? (int) round((($actuel - $precedent) / $precedent) * 100) : ($actuel > 0 ? 100 : 0);
    }

    private function appareils(array $lignes): array
    {
        $libelles = ['DESKTOP' => 'Ordinateur', 'MOBILE' => 'Mobile', 'TABLET' => 'Tablette'];

        return array_map(static function (array $ligne) use ($libelles): array {
            $type = strtoupper((string) ($ligne['keys'][0] ?? ''));
            $ligne['libelle'] = $libelles[$type] ?? ucfirst(strtolower($type));

            return $ligne;
        }, $lignes);
    }

    private function graphique(\DateTimeImmutable $debut, \DateTimeImmutable $fin, array $lignes): array
    {
        $parDate = [];
        foreach ($lignes as $ligne) {
            if (!empty($ligne['keys'][0])) {
                $parDate[(string) $ligne['keys'][0]] = $ligne;
            }
        }

        if ($debut->diff($fin)->days + 1 > 90) {
            return $this->graphiqueMensuel($debut, $fin, $lignes);
        }

        $points = [];
        $maximumClics = 1;
        $maximumImpressions = 1;
        for ($date = $debut; $date <= $fin; $date = $date->modify('+1 day')) {
            $ligne = $parDate[$date->format('Y-m-d')] ?? [];
            $clics = (int) round($ligne['clicks'] ?? 0);
            $impressions = (int) round($ligne['impressions'] ?? 0);
            $maximumClics = max($maximumClics, $clics);
            $maximumImpressions = max($maximumImpressions, $impressions);
            $points[] = [
                'date' => $date,
                'jour' => $date->format('d/m'),
                'clics' => $clics,
                'impressions' => $impressions,
            ];
        }

        return ['points' => $points, 'maximumClics' => $maximumClics, 'maximumImpressions' => $maximumImpressions];
    }

    private function graphiqueMensuel(\DateTimeImmutable $debut, \DateTimeImmutable $fin, array $lignes): array
    {
        $mois = [];
        foreach ($lignes as $ligne) {
            if (empty($ligne['keys'][0])) {
                continue;
            }
            $date = new \DateTimeImmutable((string) $ligne['keys'][0]);
            $cle = $date->format('Y-m');
            $mois[$cle] ??= ['date' => $date->modify('first day of this month'), 'jour' => $date->format('m/Y'), 'clics' => 0, 'impressions' => 0];
            $mois[$cle]['clics'] += (int) round($ligne['clicks'] ?? 0);
            $mois[$cle]['impressions'] += (int) round($ligne['impressions'] ?? 0);
        }
        $points = array_values($mois);

        return [
            'points' => $points,
            'maximumClics' => max(1, ...array_column($points, 'clics')),
            'maximumImpressions' => max(1, ...array_column($points, 'impressions')),
        ];
    }

    private function tendances(array $actuelles, array $precedentes): array
    {
        $anciensClics = [];
        foreach ($precedentes as $ligne) {
            $anciensClics[(string) ($ligne['keys'][0] ?? '')] = (int) round($ligne['clicks'] ?? 0);
        }
        $tendances = [];
        foreach ($actuelles as $ligne) {
            $cle = (string) ($ligne['keys'][0] ?? '');
            $variation = (int) round($ligne['clicks'] ?? 0) - ($anciensClics[$cle] ?? 0);
            if ($cle !== '' && $variation !== 0) {
                $tendances[] = ['libelle' => $cle, 'clics' => (int) round($ligne['clicks'] ?? 0), 'variation' => $variation];
            }
        }
        usort($tendances, static fn (array $a, array $b): int => abs($b['variation']) <=> abs($a['variation']));

        return array_slice($tendances, 0, 5);
    }

    private function opportunites(array $requetes, float $ctrMoyen): array
    {
        $opportunites = array_filter($requetes, static fn (array $ligne): bool =>
            ($ligne['impressions'] ?? 0) >= 10
            && (($ligne['ctr'] ?? 0) * 100) < $ctrMoyen
            && ($ligne['position'] ?? 0) <= 20
        );
        usort($opportunites, static fn (array $a, array $b): int => ($b['impressions'] ?? 0) <=> ($a['impressions'] ?? 0));

        return array_slice($opportunites, 0, 5);
    }

    private function prochesTop10(array $requetes): array
    {
        $lignes = array_filter($requetes, static fn (array $ligne): bool =>
            ($ligne['impressions'] ?? 0) >= 10
            && ($ligne['position'] ?? 0) >= 8
            && ($ligne['position'] ?? 0) <= 20
        );
        usort($lignes, static fn (array $a, array $b): int => ($b['impressions'] ?? 0) <=> ($a['impressions'] ?? 0));

        return array_slice($lignes, 0, 6);
    }

    /** @return array{gains: array, pertes: array} */
    private function evolutionPositions(array $pages, array $pagesPrecedentes): array
    {
        $anciennes = [];
        foreach ($pagesPrecedentes as $ligne) {
            $anciennes[(string) ($ligne['keys'][0] ?? '')] = (float) ($ligne['position'] ?? 0);
        }
        $variations = [];
        foreach ($pages as $ligne) {
            $page = (string) ($ligne['keys'][0] ?? '');
            $position = (float) ($ligne['position'] ?? 0);
            if ($page === '' || $position <= 0 || !isset($anciennes[$page]) || $anciennes[$page] <= 0) {
                continue;
            }
            $gain = round($anciennes[$page] - $position, 1);
            if (abs($gain) >= 0.5) {
                $variations[] = ['page' => $page, 'position' => $position, 'gain' => $gain];
            }
        }
        usort($variations, static fn (array $a, array $b): int => $b['gain'] <=> $a['gain']);

        return [
            'gains' => array_slice(array_values(array_filter($variations, static fn (array $ligne): bool => $ligne['gain'] > 0)), 0, 5),
            'pertes' => array_slice(array_reverse(array_values(array_filter($variations, static fn (array $ligne): bool => $ligne['gain'] < 0))), 0, 5),
        ];
    }

    private function alertes(int $evolutionClics, int $evolutionImpressions): array
    {
        $alertes = [];
        if ($evolutionClics <= -15) {
            $alertes[] = ['niveau' => 'danger', 'texte' => sprintf('Les clics Google ont baissé de %d %% par rapport à la période précédente.', abs($evolutionClics))];
        }
        if ($evolutionImpressions <= -15) {
            $alertes[] = ['niveau' => 'warning', 'texte' => sprintf('Les impressions ont baissé de %d %% par rapport à la période précédente.', abs($evolutionImpressions))];
        }

        return $alertes;
    }

    private function pays(array $lignes): array
    {
        $libelles = ['fra' => 'France', 'bel' => 'Belgique', 'che' => 'Suisse', 'can' => 'Canada', 'usa' => 'États-Unis', 'gbr' => 'Royaume-Uni', 'deu' => 'Allemagne', 'esp' => 'Espagne'];

        return array_map(static function (array $ligne) use ($libelles): array {
            $code = strtolower((string) ($ligne['keys'][0] ?? ''));
            $ligne['libelle'] = $libelles[$code] ?? strtoupper($code);

            return $ligne;
        }, $lignes);
    }

    private function viderCache(): void
    {
        foreach (self::PERIODES as $jours) {
            $this->cache->delete('search_console.performance.'.$jours);
            $this->cache->delete('search_console.performance.v2.'.$jours);
            $this->cache->delete('search_console.performance.v3.'.$jours);
            $this->cache->delete('search_console.performance.v4.'.$jours);
            foreach (['tout', 'jeux', 'actualites'] as $contenu) {
                $this->cache->delete('search_console.performance.v5.'.$jours.'.'.$contenu);
            }
        }
    }
}
