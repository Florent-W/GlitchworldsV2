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

#[AsCommand(name: 'app:import-legacy-medias', description: 'Copie les images de l’ancien site dans les dossiers de médias de la V2.')]
final class ImportLegacyMediasCommand extends Command
{
    /** @var array<string, list<string>> */
    private array $fichiersParNom = [];
    private bool $ecraser = false;

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
            ->addOption('ecraser', null, InputOption::VALUE_NONE, 'Écrase les médias déjà copiés')
            ->addOption('legacy-root', null, InputOption::VALUE_REQUIRED, 'Dossier racine contenant les fichiers du site legacy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $this->ecraser = (bool) $input->getOption('ecraser');
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

        $jeux = $this->connection->fetchAllAssociative('SELECT id, slug, miniature, banniere FROM jeu ORDER BY id');
        $actualites = $this->connection->fetchAllAssociative('SELECT id, slug, miniature, banniere, publiee_le FROM actualite ORDER BY id');
        $utilisateurs = $this->connection->fetchAllAssociative('SELECT id, avatar, banniere FROM utilisateur ORDER BY id');
        $copiesJeux = 0;
        $copiesActualites = 0;
        $copiesAvatars = 0;
        $copiesBannieresProfil = 0;
        $introuvables = [];

        foreach ((bool) $input->getOption('habillages') ? $jeux : [] as $jeu) {
            foreach (['miniature', 'banniere'] as $type) {
                $nomSource = trim((string) $jeu[$type]);
                if ('' === $nomSource) {
                    continue;
                }

                $dossierType = 'miniature' === $type ? 'miniature' : 'bandeaux';
                $source = $this->trouverFichierStrict($nomSource, [
                    '/Jeux/'.$jeu['slug'].'/'.$dossierType.'/',
                ]);
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
            foreach (['miniature', 'banniere'] as $type) {
                $valeur = trim((string) $actualite[$type]);
                $nomSource = preg_replace('/^legacy:/', '', $valeur) ?? '';
                if ('' === $nomSource || !str_starts_with($valeur, 'legacy:')) {
                    continue;
                }

                $dossierType = 'miniature' === $type ? 'miniature' : 'bandeaux';
                $date = new \DateTimeImmutable((string) $actualite['publiee_le']);
                $mois = [1 => 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'][(int) $date->format('n')];
                $source = $this->trouverFichierStrict($nomSource, [
                    '/Articles/'.$date->format('Y').'/'.$mois.'/'.$date->format('d').'/',
                    '/'.$actualite['slug'].'/'.$dossierType.'/',
                ]);
                if (null === $source) {
                    $introuvables[] = ['Actualité '.$actualite['id'], $type, $nomSource];
                    continue;
                }

                $nomCible = $type.'.'.strtolower((string) pathinfo($source, PATHINFO_EXTENSION));
                if (!$dryRun) {
                    $this->copier($source, $this->projectDir.'/public/uploads/actualites/'.$actualite['id'].'/'.$nomCible);
                    $this->connection->update('actualite', [$type => $nomCible], ['id' => (int) $actualite['id']]);
                }
                ++$copiesActualites;
            }
        }

        foreach ($utilisateurs as $utilisateur) {
            $id = (int) $utilisateur['id'];
            $avatarLegacy = trim((string) $utilisateur['avatar']);
            $sourceAvatar = $this->trouverMediaUtilisateur($legacyRoot, $id, 'photo_profil', $avatarLegacy);
            if (null !== $sourceAvatar) {
                $nomCible = 'avatar.'.strtolower((string) pathinfo($sourceAvatar, PATHINFO_EXTENSION));
                if (!$dryRun) {
                    $this->copier($sourceAvatar, $this->projectDir.'/public/uploads/utilisateurs/'.$id.'/'.$nomCible);
                    $this->connection->update('utilisateur', ['avatar' => $nomCible], ['id' => $id]);
                }
                ++$copiesAvatars;
            } elseif ('' !== $avatarLegacy && !str_starts_with($avatarLegacy, 'avatar.')) {
                $introuvables[] = ['Utilisateur '.$id, 'avatar', $avatarLegacy];
            }

            $sourceBanniere = $this->trouverMediaUtilisateur($legacyRoot, $id, 'background_site', 'background');
            if (null !== $sourceBanniere) {
                $nomCible = 'banniere.'.strtolower((string) pathinfo($sourceBanniere, PATHINFO_EXTENSION));
                if (!$dryRun) {
                    $this->copier($sourceBanniere, $this->projectDir.'/public/uploads/utilisateurs/'.$id.'/'.$nomCible);
                    $this->connection->update('utilisateur', ['banniere' => $nomCible], ['id' => $id]);
                }
                ++$copiesBannieresProfil;
            }
        }

        [$imagesContenu, $imagesContenuIntrouvables] = $this->migrerImagesContenu($dryRun);
        $introuvables = [...$introuvables, ...$imagesContenuIntrouvables];

        $io->table(['Type', 'Médias trouvés'], [
            ['Jeux', $copiesJeux],
            ['Actualités', $copiesActualites],
            ['Avatars', $copiesAvatars],
            ['Bannières de profil', $copiesBannieresProfil],
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

    private function trouverMediaUtilisateur(?string $legacyRoot, int $id, string $sousDossier, string $nomAttendu): ?string
    {
        $racine = $legacyRoot ?? dirname($this->projectDir);
        $dossiers = [
            $racine.'/utilisateurs/'.$id.'/'.$sousDossier,
            $racine.'/Glitchworld/utilisateurs/'.$id.'/'.$sousDossier,
            $racine.'/portfolio/utilisateurs/'.$id.'/'.$sousDossier,
            $racine.'/portfoliov1/utilisateurs/'.$id.'/'.$sousDossier,
        ];

        foreach ($dossiers as $dossier) {
            if (!is_dir($dossier)) {
                continue;
            }
            $fichiers = array_values(array_filter(scandir($dossier) ?: [], static fn (string $nom): bool => preg_match('/\.(?:avif|gif|jpe?g|png|webp)$/i', $nom) === 1));
            if ('' !== $nomAttendu) {
                foreach ($fichiers as $fichier) {
                    if ($this->clesNom($fichier)[0] === $this->clesNom(basename($nomAttendu))[0]) {
                        return $dossier.'/'.$fichier;
                    }
                }
            }
            if ('background_site' === $sousDossier) {
                foreach ($fichiers as $fichier) {
                    if (str_starts_with(mb_strtolower($fichier), 'background.')) {
                        return $dossier.'/'.$fichier;
                    }
                }
            }
        }

        return null;
    }

    /** @param list<string> $dossiersPreferes */
    private function trouverFichier(string $nom, array $dossiersPreferes): ?string
    {
        $candidats = [];
        foreach ($this->clesNom($nom) as $cle) {
            $candidats = [...$candidats, ...($this->fichiersParNom[$cle] ?? [])];
        }
        $candidats = array_values(array_unique($candidats));
        usort($candidats, static function (string $a, string $b) use ($dossiersPreferes): int {
            $normaliser = static fn (string $chemin): string => mb_strtolower(str_replace('\\', '/', $chemin));
            $cheminA = $normaliser($a);
            $cheminB = $normaliser($b);
            foreach ($dossiersPreferes as $dossierPrefere) {
                $preference = $normaliser($dossierPrefere);
                $aCorrespond = str_contains($cheminA, $preference);
                $bCorrespond = str_contains($cheminB, $preference);
                if ($aCorrespond !== $bCorrespond) {
                    return $aCorrespond ? -1 : 1;
                }
            }

            return strcmp($cheminA, $cheminB);
        });

        return $candidats[0] ?? null;
    }

    /** @param list<string> $fragmentsObligatoires */
    private function trouverFichierStrict(string $nom, array $fragmentsObligatoires): ?string
    {
        $candidats = [];
        foreach ($this->clesNom($nom) as $cle) {
            $candidats = [...$candidats, ...($this->fichiersParNom[$cle] ?? [])];
        }

        $normaliser = static fn (string $chemin): string => mb_strtolower(str_replace('\\', '/', $chemin));
        $fragments = array_map($normaliser, $fragmentsObligatoires);
        $candidats = array_values(array_filter(array_unique($candidats), static function (string $candidat) use ($normaliser, $fragments): bool {
            $chemin = $normaliser($candidat);

            return array_all($fragments, static fn (string $fragment): bool => str_contains($chemin, $fragment));
        }));
        sort($candidats, SORT_STRING);

        return 1 === count($candidats) ? $candidats[0] : null;
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
        if (($this->ecraser || !is_file($cible)) && !copy($source, $cible)) {
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
            $source = $this->trouverFichier(basename($reference), ['/images/']);
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
