<?php

namespace App\Command;

use App\Enum\StatutJeu;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:import-legacy-jeux',
    description: 'Importe les jeux depuis la base PHP legacy en conservant id + slug (SEO).',
)]
final class ImportLegacyJeuxCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(default::LEGACY_DATABASE_URL)%')]
        private readonly ?string $legacyDatabaseUrl = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans écrire en base V2')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Vide la table jeu V2 avant import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $purge = (bool) $input->getOption('purge');

        if (!$this->legacyDatabaseUrl) {
            $io->error('Variable LEGACY_DATABASE_URL manquante dans .env.local');
            $io->writeln('Exemple : LEGACY_DATABASE_URL="mysql://root:@127.0.0.1:3306/glitchworld?serverVersion=8.0.31&charset=utf8mb4"');

            return Command::FAILURE;
        }

        $parser = new DsnParser(['mysql' => 'pdo_mysql', 'mysqli' => 'mysqli']);
        $legacy = DriverManager::getConnection($parser->parse($this->legacyDatabaseUrl));

        $categories = $legacy->fetchAllAssociative(
            'SELECT id, nom FROM categorie_jeu ORDER BY id ASC'
        );

        $plateformes = $legacy->fetchAllAssociative(
            'SELECT id, nom_plateforme, nom_image FROM plateformes ORDER BY id ASC'
        );

        $liaisonsPlateformes = $legacy->fetchAllAssociative(
            'SELECT id_jeu, id_plateforme FROM jeu_lier_plateformes ORDER BY id_jeu ASC, id_plateforme ASC'
        );

        $genres = $legacy->fetchAllAssociative(
            'SELECT id, genre, nom_image FROM genres ORDER BY id ASC'
        );

        $liaisonsGenres = $legacy->fetchAllAssociative(
            'SELECT id_jeu, id_genre FROM jeu_lier_genres ORDER BY id_jeu ASC, id_genre ASC'
        );

        $langues = $legacy->fetchAllAssociative(
            'SELECT id, langue, nom_image FROM langues ORDER BY id ASC'
        );

        $liaisonsLangues = $legacy->fetchAllAssociative(
            'SELECT id_jeu, id_langue FROM jeu_lier_langues ORDER BY id_jeu ASC, id_langue ASC'
        );

        $rows = $legacy->fetchAllAssociative(
            'SELECT j.id, j.nom, j.contenu, j.nom_miniature, j.date_sortie, j.url, j.nom_banniere, j.approuver, j.description,
                    j.id_categorie, u.pseudo AS developpeur
             FROM jeu j
             LEFT JOIN utilisateurs u ON u.id = j.id_auteur_presentation
             ORDER BY j.id ASC'
        );

        if ($rows === []) {
            $io->warning('Aucun jeu trouvé dans la base legacy.');

            return Command::SUCCESS;
        }

        $io->title(sprintf('Import de %d jeux legacy → V2', \count($rows)));

        $slugPlan = $this->preparerSlugs($rows);
        $conflicts = array_filter($slugPlan, static fn (array $item): bool => $item['slug_changed']);

        if ($conflicts !== []) {
            $io->section('Conflits de slugs (doublons legacy)');
            $io->table(
                ['id', 'slug legacy', 'slug V2', 'statut'],
                array_map(static fn (array $item): array => [
                    $item['id'],
                    $item['legacy_slug'],
                    $item['slug'],
                    $item['statut']->value,
                ], $conflicts)
            );
            $io->note('Les doublons reçoivent un suffixe -{id} pour respecter l’unicité V2. Les URLs SEO /jeu/{slug}-{id} restent valides via l’id conservé.');
        }

        if ($dryRun) {
            $io->table(
                ['id', 'nom', 'slug', 'categorie', 'statut'],
                array_map(static fn (array $item): array => [
                    $item['id'],
                    mb_substr($item['nom'], 0, 40),
                    $item['slug'],
                    $item['categorie_id'] ?? '-',
                    $item['statut']->value,
                ], \array_slice($slugPlan, 0, 15))
            );
            $io->success(sprintf(
                'Dry-run OK - %d catégories, %d plateformes, %d liaisons, %d jeux prêts (%d slugs renommés). Rien n’a été écrit.',
                \count($categories),
                \count($plateformes),
                \count($liaisonsPlateformes),
                \count($slugPlan),
                \count($conflicts)
            ));

            return Command::SUCCESS;
        }

        if ($purge) {
            $this->connection->executeStatement('DELETE FROM jeu_langue');
            $this->connection->executeStatement('DELETE FROM jeu_genre');
            $this->connection->executeStatement('DELETE FROM jeu_plateforme');
            $this->connection->executeStatement('DELETE FROM jeu');
            $this->connection->executeStatement('DELETE FROM langue');
            $this->connection->executeStatement('DELETE FROM genre');
            $this->connection->executeStatement('DELETE FROM plateforme');
            $this->connection->executeStatement('DELETE FROM categorie_jeu');
            $io->writeln('Tables jeu, plateforme, categorie_jeu et liaisons V2 purgées.');
        }

        $this->importerCategories($categories);
        $io->writeln(sprintf('%d catégories synchronisées.', \count($categories)));

        $this->importerPlateformes($plateformes);
        $this->importerGenres($genres);
        $this->importerLangues($langues);
        $io->writeln(sprintf('%d plateformes synchronisées.', \count($plateformes)));

        $inserted = 0;
        $updated = 0;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach ($slugPlan as $item) {
            $payload = [
                'id' => $item['id'],
                'nom' => $item['nom'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'contenu' => $item['contenu'],
                'date_sortie' => $item['date_sortie'],
                'statut' => $item['statut']->value,
                'miniature' => $item['miniature'],
                'banniere' => $item['banniere'],
                'developpeur' => $item['developpeur'],
                'categorie_id' => $item['categorie_id'],
                'cree_le' => $now,
                'modifie_le' => $now,
            ];

            $exists = (bool) $this->connection->fetchOne('SELECT 1 FROM jeu WHERE id = ?', [$item['id']]);

            if ($exists) {
                unset($payload['id'], $payload['cree_le']);
                $this->connection->update('jeu', $payload, ['id' => $item['id']]);
                ++$updated;
            } else {
                $this->connection->insert('jeu', $payload);
                ++$inserted;
            }
        }

        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id) FROM jeu');
        $this->connection->executeStatement('ALTER TABLE jeu AUTO_INCREMENT = '.($maxId + 1));

        $nbLiaisons = $this->importerLiaisonsPlateformes($liaisonsPlateformes);
        $nbLiaisonsGenres = $this->importerLiaisonsGenres($liaisonsGenres);
        $nbLiaisonsLangues = $this->importerLiaisonsLangues($liaisonsLangues);
        $io->writeln(sprintf('%d liaisons jeu/plateforme synchronisées.', $nbLiaisons));

        $io->success(sprintf(
            'Import terminé - %d insérés, %d mis à jour, %d slugs ajustés (doublons). AUTO_INCREMENT = %d',
            $inserted,
            $updated,
            \count($conflicts),
            $maxId + 1
        ));

        return Command::SUCCESS;
    }

    /**
     * @param list<array<string, mixed>> $lignes
     *
     * @return list<array{
     *     id: int,
     *     nom: string,
     *     legacy_slug: string,
     *     slug: string,
     *     slug_changed: bool,
     *     description: string,
     *     contenu: string,
     *     date_sortie: ?string,
     *     statut: StatutJeu,
     *     miniature: ?string,
     *     banniere: ?string,
     *     developpeur: ?string,
     *     categorie_id: ?int
     * }>
     */
    private function preparerSlugs(array $lignes): array
    {
        $prepares = [];
        foreach ($lignes as $ligne) {
            $categorieId = $ligne['id_categorie'] ?? null;
            $prepares[] = [
                'id' => (int) $ligne['id'],
                'nom' => (string) $ligne['nom'],
                'legacy_slug' => $this->normaliserSlug((string) $ligne['url']),
                'description' => mb_substr((string) $ligne['description'], 0, 160),
                'contenu' => (string) $ligne['contenu'],
                'date_sortie' => $ligne['date_sortie'] ?: null,
                'statut' => $this->convertirStatut((string) $ligne['approuver']),
                'miniature' => $this->chaineOuNull($ligne['nom_miniature'] ?? null),
                'banniere' => $this->chaineOuNull($ligne['nom_banniere'] ?? null),
                'developpeur' => $this->chaineOuNull($ligne['developpeur'] ?? null),
                'categorie_id' => $categorieId !== null && $categorieId !== '' ? (int) $categorieId : null,
            ];
        }

        $parSlug = [];
        foreach ($prepares as $index => $item) {
            $parSlug[$item['legacy_slug']][] = $index;
        }

        $plan = [];
        foreach ($prepares as $index => $item) {
            $freres = $parSlug[$item['legacy_slug']];
            $slug = $item['legacy_slug'];
            $modifie = false;

            if (\count($freres) > 1) {
                $indexGagnant = $this->choisirIndexGagnant($prepares, $freres);
                if ($indexGagnant !== $index) {
                    $slug = mb_substr($item['legacy_slug'].'-'.$item['id'], 0, 180);
                    $modifie = true;
                }
            }

            $plan[] = [
                ...$item,
                'slug' => $slug,
                'slug_changed' => $modifie,
            ];
        }

        return $plan;
    }

    /**
     * @param list<array<string, mixed>> $prepares
     * @param list<int>                  $indexes
     */
    private function choisirIndexGagnant(array $prepares, array $indexes): int
    {
        usort($indexes, static function (int $a, int $b) use ($prepares): int {
            $scoreA = $prepares[$a]['statut']->value;
            $scoreB = $prepares[$b]['statut']->value;
            $rang = [
                StatutJeu::Approuve->value => 3,
                StatutJeu::EnAttente->value => 2,
                StatutJeu::Brouillon->value => 1,
                StatutJeu::Refuse->value => 0,
            ];
            $diff = ($rang[$scoreB] ?? 0) <=> ($rang[$scoreA] ?? 0);
            if ($diff !== 0) {
                return $diff;
            }

            return $prepares[$b]['id'] <=> $prepares[$a]['id'];
        });

        return $indexes[0];
    }

    /**
     * @param list<array{id: int|string, nom: string}> $categories
     */
    private function importerCategories(array $categories): void
    {
        foreach ($categories as $categorie) {
            $id = (int) $categorie['id'];
            $payload = [
                'id' => $id,
                'nom' => (string) $categorie['nom'],
                'slug' => $this->normaliserSlug((string) $categorie['nom']),
            ];

            $exists = (bool) $this->connection->fetchOne('SELECT 1 FROM categorie_jeu WHERE id = ?', [$id]);
            if ($exists) {
                unset($payload['id']);
                $this->connection->update('categorie_jeu', $payload, ['id' => $id]);
            } else {
                $this->connection->insert('categorie_jeu', $payload);
            }
        }

        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id) FROM categorie_jeu');
        if ($maxId > 0) {
            $this->connection->executeStatement('ALTER TABLE categorie_jeu AUTO_INCREMENT = '.($maxId + 1));
        }
    }

    /**
     * @param list<array{id: int|string, nom_plateforme: string, nom_image: ?string}> $plateformes
     */
    private function importerPlateformes(array $plateformes): void
    {
        foreach ($plateformes as $plateforme) {
            $id = (int) $plateforme['id'];
            $payload = [
                'id' => $id,
                'nom' => (string) $plateforme['nom_plateforme'],
                'slug' => $this->normaliserSlug((string) $plateforme['nom_plateforme']),
                'image' => $this->chaineOuNull($plateforme['nom_image'] ?? null),
            ];

            $exists = (bool) $this->connection->fetchOne('SELECT 1 FROM plateforme WHERE id = ?', [$id]);
            if ($exists) {
                unset($payload['id']);
                $this->connection->update('plateforme', $payload, ['id' => $id]);
            } else {
                $this->connection->insert('plateforme', $payload);
            }
        }

        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id) FROM plateforme');
        if ($maxId > 0) {
            $this->connection->executeStatement('ALTER TABLE plateforme AUTO_INCREMENT = '.($maxId + 1));
        }
    }

    /**
     * @param list<array{id_jeu: int|string, id_plateforme: int|string}> $liaisons
     */
    private function importerLiaisonsPlateformes(array $liaisons): int
    {
        $this->connection->executeStatement('DELETE FROM jeu_plateforme');

        $inserted = 0;
        foreach ($liaisons as $liaison) {
            $jeuId = (int) $liaison['id_jeu'];
            $plateformeId = (int) $liaison['id_plateforme'];

            $jeuExiste = (bool) $this->connection->fetchOne('SELECT 1 FROM jeu WHERE id = ?', [$jeuId]);
            $plateformeExiste = (bool) $this->connection->fetchOne('SELECT 1 FROM plateforme WHERE id = ?', [$plateformeId]);
            if (!$jeuExiste || !$plateformeExiste) {
                continue;
            }

            $this->connection->insert('jeu_plateforme', [
                'jeu_id' => $jeuId,
                'plateforme_id' => $plateformeId,
            ]);
            ++$inserted;
        }

        return $inserted;
    }

    /**
     * @param list<array{id: int|string, genre: string, nom_image: ?string}> $genres
     */
    private function importerGenres(array $genres): void
    {
        foreach ($genres as $genre) {
            $id = (int) $genre['id'];
            $payload = [
                'id' => $id,
                'nom' => (string) $genre['genre'],
                'slug' => $this->normaliserSlug((string) $genre['genre']),
                'image' => $this->chaineOuNull($genre['nom_image'] ?? null),
            ];

            $exists = (bool) $this->connection->fetchOne('SELECT 1 FROM genre WHERE id = ?', [$id]);
            if ($exists) {
                unset($payload['id']);
                $this->connection->update('genre', $payload, ['id' => $id]);
            } else {
                $this->connection->insert('genre', $payload);
            }
        }

        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id) FROM genre');
        if ($maxId > 0) {
            $this->connection->executeStatement('ALTER TABLE genre AUTO_INCREMENT = '.($maxId + 1));
        }
    }

    /**
     * @param list<array{id_jeu: int|string, id_genre: int|string}> $liaisons
     */
    private function importerLiaisonsGenres(array $liaisons): int
    {
        $this->connection->executeStatement('DELETE FROM jeu_genre');

        $inserted = 0;
        foreach ($liaisons as $liaison) {
            $jeuId = (int) $liaison['id_jeu'];
            $genreId = (int) $liaison['id_genre'];

            $jeuExiste = (bool) $this->connection->fetchOne('SELECT 1 FROM jeu WHERE id = ?', [$jeuId]);
            $genreExiste = (bool) $this->connection->fetchOne('SELECT 1 FROM genre WHERE id = ?', [$genreId]);
            if (!$jeuExiste || !$genreExiste) {
                continue;
            }

            $this->connection->insert('jeu_genre', [
                'jeu_id' => $jeuId,
                'genre_id' => $genreId,
            ]);
            ++$inserted;
        }

        return $inserted;
    }

    /**
     * @param list<array{id: int|string, langue: string, nom_image: ?string}> $langues
     */
    private function importerLangues(array $langues): void
    {
        foreach ($langues as $langue) {
            $id = (int) $langue['id'];
            $nom = match ($id) {
                1 => 'Français',
                9 => 'Coréen',
                default => (string) $langue['langue'],
            };
            $payload = [
                'id' => $id,
                'nom' => $nom,
                'slug' => $this->normaliserSlug($nom),
                'image' => $this->chaineOuNull($langue['nom_image'] ?? null),
            ];

            $exists = (bool) $this->connection->fetchOne('SELECT 1 FROM langue WHERE id = ?', [$id]);
            if ($exists) {
                unset($payload['id']);
                $this->connection->update('langue', $payload, ['id' => $id]);
            } else {
                $this->connection->insert('langue', $payload);
            }
        }

        $maxId = (int) $this->connection->fetchOne('SELECT MAX(id) FROM langue');
        if ($maxId > 0) {
            $this->connection->executeStatement('ALTER TABLE langue AUTO_INCREMENT = '.($maxId + 1));
        }
    }

    /**
     * @param list<array{id_jeu: int|string, id_langue: int|string}> $liaisons
     */
    private function importerLiaisonsLangues(array $liaisons): int
    {
        $this->connection->executeStatement('DELETE FROM jeu_langue');

        $inserted = 0;
        foreach ($liaisons as $liaison) {
            $jeuId = (int) $liaison['id_jeu'];
            $langueId = (int) $liaison['id_langue'];

            $jeuExiste = (bool) $this->connection->fetchOne('SELECT 1 FROM jeu WHERE id = ?', [$jeuId]);
            $langueExiste = (bool) $this->connection->fetchOne('SELECT 1 FROM langue WHERE id = ?', [$langueId]);
            if (!$jeuExiste || !$langueExiste) {
                continue;
            }

            $this->connection->insert('jeu_langue', [
                'jeu_id' => $jeuId,
                'langue_id' => $langueId,
            ]);
            ++$inserted;
        }

        return $inserted;
    }

    private function convertirStatut(string $legacy): StatutJeu
    {
        $normalise = mb_strtolower(trim($legacy));
        $normalise = strtr($normalise, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'à' => 'a',
        ]);

        return match ($normalise) {
            'approuver', 'approuve' => StatutJeu::Approuve,
            'preapprouver', 'preapprouve' => StatutJeu::EnAttente,
            'brouillon' => StatutJeu::Brouillon,
            'refuse', 'refuser' => StatutJeu::Refuse,
            default => StatutJeu::Brouillon,
        };
    }

    private function normaliserSlug(string $slug): string
    {
        $slug = (new AsciiSlugger('fr'))->slug($slug)->lower()->toString();

        return mb_substr($slug !== '' ? $slug : 'jeu', 0, 180);
    }

    private function chaineOuNull(mixed $valeur): ?string
    {
        if ($valeur === null) {
            return null;
        }

        $valeur = trim((string) $valeur);

        return $valeur === '' ? null : $valeur;
    }
}
