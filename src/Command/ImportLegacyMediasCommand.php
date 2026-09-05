<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:import-legacy-medias', description: 'Copie les miniatures et bannières de l’ancien site dans public/uploads.')]
final class ImportLegacyMediasCommand extends Command
{
    /** @var array<string, list<string>> */
    private array $fichiersParNom = [];

    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les médias trouvés sans copier les fichiers')
            ->addOption('habillages', null, InputOption::VALUE_NONE, 'Migre aussi les miniatures et les bannières')
            ->addOption('legacy-root', null, InputOption::VALUE_REQUIRED, 'Dossier racine contenant les fichiers du site legacy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $legacyRoot = $input->getOption('legacy-root');
        if (is_string($legacyRoot) && '' !== trim($legacyRoot)) {
            $legacyRoot = realpath(trim($legacyRoot));
            if (false === $legacyRoot || !is_dir($legacyRoot)) {
                $io->error('Le dossier indiqué par --legacy-root est introuvable ou illisible.');

                return Command::FAILURE;
            }
            $io->note('Fichiers legacy recherchés dans : '.$legacyRoot);
        } else {
            $legacyRoot = null;
        }
        $this->indexerFichiers($legacyRoot);

        $jeux = $this->connection->fetchAllAssociative('SELECT id, miniature, banniere FROM jeu ORDER BY id');
        $actualites = $this->connection->fetchAllAssociative('SELECT id, miniature FROM actualite ORDER BY id');
        $copiesJeux = 0;
        $copiesActualites = 0;
        $introuvables = [];

        foreach ((bool) $input->getOption('habillages') ? $jeux : [] as $jeu) {
            foreach (['miniature', 'banniere'] as $type) {
                $nomSource = trim((string) $jeu[$type]);
                if ('' === $nomSource) {
                    continue;
                }

                $source = $this->trouverFichier($nomSource, 'Jeux');
                if (null === $source) {
                    $introuvables[] = ['Jeu '.$jeu['id'], $type, $nomSource];
                    continue;
                }

                $nomCible = $type.'.'.strtolower((string) pathinfo($source, PATHINFO_EXTENSION));
                if (!$dryRun) {
                    $this->copier($source, $this->projectDir.'/public/uploads/jeux/'.$jeu['id'].'/'.$nomCible);
                    $this->connection->update('jeu', [$type => $nomCible], ['id' => (int) $jeu['id']]);
                }
                ++$copiesJeux;
            }
        }

        foreach ((bool) $input->getOption('habillages') ? $actualites : [] as $actualite) {
            $nomSource = preg_replace('/^legacy:/', '', trim((string) $actualite['miniature'])) ?? '';
            if ('' === $nomSource || !str_starts_with((string) $actualite['miniature'], 'legacy:')) {
                continue;
            }

            $source = $this->trouverFichier($nomSource, 'Articles');
            if (null === $source) {
                $introuvables[] = ['Actualité '.$actualite['id'], 'miniature', $nomSource];
                continue;
            }

            $nomCible = 'miniature.'.strtolower((string) pathinfo($source, PATHINFO_EXTENSION));
            if (!$dryRun) {
                $this->copier($source, $this->projectDir.'/public/uploads/actualites/'.$actualite['id'].'/'.$nomCible);
                $this->connection->update('actualite', ['miniature' => $nomCible], ['id' => (int) $actualite['id']]);
            }
            ++$copiesActualites;
        }

        [$imagesContenu, $imagesContenuIntrouvables] = $this->migrerImagesContenu($dryRun);
        $introuvables = [...$introuvables, ...$imagesContenuIntrouvables];

        $io->table(['Type', 'Médias trouvés'], [
            ['Jeux', $copiesJeux],
            ['Actualités', $copiesActualites],
            ['Contenus', $imagesContenu],
        ]);
        if ([] !== $introuvables) {
            $io->warning(sprintf('%d médias restent introuvables.', count($introuvables)));
            $io->table(['Élément', 'Type', 'Ancien nom'], array_slice($introuvables, 0, 20));
        }
        $io->success($dryRun ? 'Analyse terminée, aucun fichier copié.' : 'Migration des médias terminée.');

        return Command::SUCCESS;
    }

    private function indexerFichiers(?string $legacyRoot = null): void
    {
        $racineLegacy = $legacyRoot ?? dirname($this->projectDir);
        $dossiers = [
            $racineLegacy.'/miniature',
            $racineLegacy.'/bandeaux',
            $racineLegacy.'/Jeux',
            $racineLegacy.'/Articles',
            $racineLegacy.'/images',
            $racineLegacy.'/portfolio/miniature',
            $racineLegacy.'/portfolio/bandeaux',
            $racineLegacy.'/portfolio/Jeux',
            $racineLegacy.'/portfolio/Articles',
            $racineLegacy.'/portfolio/images',
            $racineLegacy.'/Glitchworld/Jeux',
            $racineLegacy.'/Glitchworld/Articles',
            $racineLegacy.'/Glitchworld/miniature',
            $racineLegacy.'/Glitchworld/bandeaux',
            $racineLegacy.'/portfoliov1/Jeux',
            $racineLegacy.'/portfoliov1/Articles',
            $racineLegacy.'/portfoliov1/miniature',
            $racineLegacy.'/portfoliov1/bandeaux',
            $racineLegacy.'/portfoliov1/images',
            $racineLegacy.'/Glitchworld/images',
            $this->projectDir.'/images',
            $this->projectDir.'/Articles',
            $this->projectDir.'/Jeux',
        ];

        foreach (array_unique($dossiers) as $dossier) {
            if (!is_dir($dossier)) {
                continue;
            }
            $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dossier, \FilesystemIterator::SKIP_DOTS));
            foreach ($fichiers as $fichier) {
                if (!$fichier->isFile()) {
                    continue;
                }
                foreach ($this->clesNom($fichier->getFilename()) as $cle) {
                    $this->fichiersParNom[$cle][] = $fichier->getPathname();
                }
            }
        }
    }

    private function trouverFichier(string $nom, string $dossierPrefere): ?string
    {
        $candidats = [];
        foreach ($this->clesNom($nom) as $cle) {
            $candidats = [...$candidats, ...($this->fichiersParNom[$cle] ?? [])];
        }
        $candidats = array_values(array_unique($candidats));
        usort($candidats, static fn (string $a, string $b): int => (int) !str_contains($a, $dossierPrefere) <=> (int) !str_contains($b, $dossierPrefere));

        return $candidats[0] ?? null;
    }

    /** @return list<string> */
    private function clesNom(string $nom): array
    {
        $variantes = [$nom];
        $octets = @mb_convert_encoding($nom, 'Windows-1252', 'UTF-8');
        if (false !== $octets) {
            $variantes[] = mb_convert_encoding($octets, 'UTF-8', 'CP850');
        }

        return array_values(array_unique(array_map(static function (string $valeur): string {
            $valeur = mb_strtolower($valeur);
            $valeur = transliterator_transliterate('Any-Latin; Latin-ASCII', $valeur) ?: $valeur;

            return preg_replace('/[^a-z0-9.]+/', '', $valeur) ?? $valeur;
        }, $variantes)));
    }

    private function copier(string $source, string $cible): void
    {
        $dossier = dirname($cible);
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier %s.', $dossier));
        }
        if (!is_file($cible) && !copy($source, $cible)) {
            throw new \RuntimeException(sprintf('Impossible de copier %s.', $source));
        }
    }

    /** @return array{int, list<array{string, string, string}>} */
    private function migrerImagesContenu(bool $dryRun): array
    {
        $contenus = $this->connection->fetchFirstColumn(
            'SELECT contenu FROM jeu UNION ALL SELECT contenu FROM actualite',
        );
        $references = [];

        foreach ($contenus as $contenu) {
            preg_match_all('/\[image2(?:=[^\]]+)?\](.+?)\[\/image2\]/si', (string) $contenu, $correspondances);
            foreach ($correspondances[1] ?? [] as $reference) {
                $reference = trim(html_entity_decode((string) $reference, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (
                    '' !== $reference
                    && mb_strlen($reference) <= 240
                    && !str_contains($reference, '..')
                    && !preg_match('#^(?:https?:)?//#i', $reference)
                    && !preg_match('/[<>:"|?*]/', $reference)
                    && preg_match('/\.(?:avif|gif|jpe?g|png|webp)$/i', $reference)
                ) {
                    $references[str_replace('\\', '/', $reference)] = true;
                }
            }
        }

        $copies = 0;
        $introuvables = [];
        foreach (array_keys($references) as $reference) {
            $source = $this->trouverFichier(basename($reference), 'images');
            if (null === $source) {
                $introuvables[] = ['Contenu', 'image', $reference];
                continue;
            }

            if (!$dryRun) {
                $this->copier($source, $this->projectDir.'/public/images/'.$reference);
            }
            ++$copies;
        }

        return [$copies, $introuvables];
    }
}
