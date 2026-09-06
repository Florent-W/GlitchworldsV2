<?php

namespace App\Command;

use App\Service\ConvertisseurSectionsJeu;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:convertir-sections-jeux',
    description: 'Convertit les titres principaux des anciennes fiches en sections V2.',
)]
final class ConvertirSectionsJeuxCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ConvertisseurSectionsJeu $convertisseur,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse sans modifier la base')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Convertit uniquement le jeu indiqué');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $id = $input->getOption('id');
        $parametres = [];
        $sql = 'SELECT id, nom, contenu FROM jeu WHERE contenu <> \'\'';
        if ($id !== null) {
            $sql .= ' AND id = :id';
            $parametres['id'] = (int) $id;
        }
        $sql .= ' ORDER BY id ASC';

        $jeux = $this->connection->fetchAllAssociative($sql, $parametres);
        $conversions = [];
        $ignores = [];
        foreach ($jeux as $jeu) {
            $resultat = $this->convertisseur->convertir((string) $jeu['contenu']);
            if ($resultat['raison'] !== null) {
                $ignores[$resultat['raison']] = ($ignores[$resultat['raison']] ?? 0) + 1;
                continue;
            }

            $conversions[] = [
                'id' => (int) $jeu['id'],
                'nom' => (string) $jeu['nom'],
                'ancien_contenu' => (string) $jeu['contenu'],
                'nouveau_contenu' => $resultat['contenu'],
                'sections' => $resultat['sections'],
            ];
        }

        $io->title($dryRun ? 'Simulation de conversion des fiches' : 'Conversion des fiches');
        $io->table(
            ['Jeux analysés', 'Convertibles', 'Sections créées'],
            [[count($jeux), count($conversions), array_sum(array_column($conversions, 'sections'))]],
        );
        foreach ($ignores as $raison => $total) {
            $io->writeln(sprintf(' - %s : %d', str_replace('_', ' ', $raison), $total));
        }

        if ($dryRun || $conversions === []) {
            $io->success($dryRun ? 'Simulation terminée, aucune donnée modifiée.' : 'Aucune fiche à convertir.');

            return Command::SUCCESS;
        }

        $dossierSauvegarde = $this->projectDir.'/var/backups';
        if (!is_dir($dossierSauvegarde) && !mkdir($dossierSauvegarde, 0775, true) && !is_dir($dossierSauvegarde)) {
            $io->error('Impossible de créer le dossier de sauvegarde.');

            return Command::FAILURE;
        }
        $fichierSauvegarde = $dossierSauvegarde.'/sections-jeux-'.date('Ymd-His').'.json';
        $sauvegarde = array_map(
            static fn (array $jeu): array => ['id' => $jeu['id'], 'nom' => $jeu['nom'], 'contenu' => $jeu['ancien_contenu']],
            $conversions,
        );
        if (file_put_contents($fichierSauvegarde, json_encode($sauvegarde, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) === false) {
            $io->error('Impossible d’écrire la sauvegarde. La conversion est annulée.');

            return Command::FAILURE;
        }

        $this->connection->transactional(function () use ($conversions): void {
            foreach ($conversions as $jeu) {
                $this->connection->update('jeu', ['contenu' => $jeu['nouveau_contenu']], ['id' => $jeu['id']]);
            }
        });

        $io->success(sprintf('%d fiches converties. Sauvegarde : %s', count($conversions), $fichierSauvegarde));

        return Command::SUCCESS;
    }
}
