<?php

namespace App\Command;

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
    name: 'app:import-legacy-background-videos',
    description: 'Importe les vidéos de fond des jeux et les préférences vidéo des utilisateurs legacy.',
)]
final class ImportLegacyBackgroundVideosCommand extends Command
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
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse les données sans modifier la base V2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if (!$this->legacyDatabaseUrl) {
            $io->error('Variable LEGACY_DATABASE_URL manquante dans .env.local');
            return Command::FAILURE;
        }

        $parser = new DsnParser(['mysql' => 'pdo_mysql', 'mysqli' => 'mysqli']);
        $legacy = DriverManager::getConnection($parser->parse($this->legacyDatabaseUrl));

        $jeux = $legacy->fetchAllAssociative('SELECT id, video_background FROM jeu ORDER BY id ASC');
        $utilisateurs = $legacy->fetchAllAssociative('SELECT id, activer_video_background, activer_son_video_background FROM utilisateurs ORDER BY id ASC');

        $statistiques = [
            'jeux' => count($jeux),
            'jeux_mis_a_jour' => 0,
            'jeux_absents' => 0,
            'utilisateurs' => count($utilisateurs),
            'utilisateurs_mis_a_jour' => 0,
            'utilisateurs_absents' => 0,
        ];

        $this->connection->beginTransaction();

        try {
            foreach ($jeux as $jeu) {
                $id = (int) $jeu['id'];
                $existe = $this->connection->fetchOne('SELECT 1 FROM jeu WHERE id = ?', [$id]);

                if (!$existe) {
                    ++$statistiques['jeux_absents'];
                    continue;
                }

                if (!$dryRun) {
                    $this->connection->update('jeu', ['video_background' => $jeu['video_background']], ['id' => $id]);
                }

                ++$statistiques['jeux_mis_a_jour'];
            }

            foreach ($utilisateurs as $utilisateur) {
                $id = (int) $utilisateur['id'];
                $existe = $this->connection->fetchOne('SELECT 1 FROM utilisateur WHERE id = ?', [$id]);

                if (!$existe) {
                    ++$statistiques['utilisateurs_absents'];
                    continue;
                }

                $videoBackgroundActive = false;
                $videoBackgroundSoundActive = false;

                if ($utilisateur['activer_video_background'] === 'true') {
                    $videoBackgroundActive = true;
                }

                if ($utilisateur['activer_son_video_background'] === 'true') {
                    $videoBackgroundSoundActive = true;
                }

                if (!$dryRun) {
                    $this->connection->update('utilisateur', [
                        'video_background_active' => $videoBackgroundActive,
                        'video_background_sound_active' => $videoBackgroundSoundActive,
                    ], ['id' => $id]);
                }

                ++$statistiques['utilisateurs_mis_a_jour'];
            }

            if ($dryRun) {
                $this->connection->rollBack();
            } else {
                $this->connection->commit();
            }
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $io->table(['Résultat', 'Nombre'], [
            ['Jeux legacy', $statistiques['jeux']],
            ['Jeux V2 synchronisés', $statistiques['jeux_mis_a_jour']],
            ['Jeux absents de la V2', $statistiques['jeux_absents']],
            ['Utilisateurs legacy', $statistiques['utilisateurs']],
            ['Utilisateurs V2 synchronisés', $statistiques['utilisateurs_mis_a_jour']],
            ['Utilisateurs absents de la V2', $statistiques['utilisateurs_absents']],
        ]);

        if ($dryRun) {
            $io->success('Analyse terminée, aucune donnée modifiée.');
        } else {
            $io->success('Les backgrounds vidéo legacy ont été importés.');
        }

        return Command::SUCCESS;
    }
}