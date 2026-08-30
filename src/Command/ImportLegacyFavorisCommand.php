<?php

namespace App\Command;

use App\Enum\StatutBibliotheque;
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

#[AsCommand(
    name: 'app:import-legacy-favoris',
    description: 'Importe les favoris legacy (favoris_jeu) vers V2, avec option bibliothèque.',
)]
final class ImportLegacyFavorisCommand extends Command
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
            ->addOption('bibliotheque', null, InputOption::VALUE_NONE, 'Copie aussi chaque favori dans jeu_bibliotheque (succès collectionneur)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $bibliotheque = (bool) $input->getOption('bibliotheque');

        if (!$this->legacyDatabaseUrl) {
            $io->error('Variable LEGACY_DATABASE_URL manquante dans .env.local');

            return Command::FAILURE;
        }

        $legacy = DriverManager::getConnection((new DsnParser([
            'mysql' => 'pdo_mysql',
            'mysqli' => 'mysqli',
        ]))->parse($this->legacyDatabaseUrl));

        $favoris = $legacy->fetchAllAssociative(
            'SELECT id_utilisateur, id_jeu FROM favoris_jeu ORDER BY id_utilisateur ASC, id_jeu ASC'
        );

        if ($favoris === []) {
            $io->warning('Aucun favori trouvé dans la base legacy.');

            return Command::SUCCESS;
        }

        $io->title(sprintf('Import de %d favoris legacy → V2', \count($favoris)));

        $stats = [
            'favoris_inseres' => 0,
            'favoris_existants' => 0,
            'favoris_ignores' => 0,
            'bibliotheque_inseres' => 0,
            'bibliotheque_existants' => 0,
        ];

        foreach ($favoris as $favori) {
            $utilisateurId = (int) $favori['id_utilisateur'];
            $jeuId = (int) $favori['id_jeu'];

            if (!(bool) $this->connection->fetchOne('SELECT 1 FROM utilisateur WHERE id = ?', [$utilisateurId])) {
                ++$stats['favoris_ignores'];
                continue;
            }
            if (!(bool) $this->connection->fetchOne('SELECT 1 FROM jeu WHERE id = ?', [$jeuId])) {
                ++$stats['favoris_ignores'];
                continue;
            }

            $existe = (bool) $this->connection->fetchOne(
                'SELECT 1 FROM utilisateur_jeu_favori WHERE utilisateur_id = ? AND jeu_id = ?',
                [$utilisateurId, $jeuId],
            );

            if ($existe) {
                ++$stats['favoris_existants'];
            } elseif (!$dryRun) {
                $this->connection->insert('utilisateur_jeu_favori', [
                    'utilisateur_id' => $utilisateurId,
                    'jeu_id' => $jeuId,
                ]);
                ++$stats['favoris_inseres'];
            } else {
                ++$stats['favoris_inseres'];
            }

            if (!$bibliotheque) {
                continue;
            }

            $bibliothequeExiste = (bool) $this->connection->fetchOne(
                'SELECT 1 FROM jeu_bibliotheque WHERE utilisateur_id = ? AND jeu_id = ?',
                [$utilisateurId, $jeuId],
            );

            if ($bibliothequeExiste) {
                ++$stats['bibliotheque_existants'];
            } elseif (!$dryRun) {
                $this->connection->insert('jeu_bibliotheque', [
                    'utilisateur_id' => $utilisateurId,
                    'jeu_id' => $jeuId,
                    'statut' => StatutBibliotheque::A_Jouer->value,
                    'ajoute_le' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);
                ++$stats['bibliotheque_inseres'];
            } else {
                ++$stats['bibliotheque_inseres'];
            }
        }

        $io->table(['Statistique', 'Valeur'], [
            ['Favoris insérés', $stats['favoris_inseres']],
            ['Favoris déjà présents', $stats['favoris_existants']],
            ['Favoris ignorés (membre/jeu absent)', $stats['favoris_ignores']],
            ['Bibliothèque insérée', $stats['bibliotheque_inseres']],
            ['Bibliothèque déjà présente', $stats['bibliotheque_existants']],
        ]);

        $io->success($dryRun ? 'Dry-run terminé : aucune écriture.' : 'Import des favoris terminé.');

        return Command::SUCCESS;
    }
}
