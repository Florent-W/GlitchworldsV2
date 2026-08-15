<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:import-legacy-reactions', description: 'Importe et déduplique les mentions J’aime des anciens commentaires.')]
final class ImportLegacyReactionsCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%env(default::LEGACY_DATABASE_URL)%')]
        private readonly ?string $legacyDatabaseUrl = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->legacyDatabaseUrl) {
            $io->error('Variable LEGACY_DATABASE_URL manquante.');

            return Command::FAILURE;
        }

        $legacy = DriverManager::getConnection((new DsnParser([
            'mysql' => 'pdo_mysql',
            'mysqli' => 'mysqli',
        ]))->parse($this->legacyDatabaseUrl));

        $configurations = [
            ['source' => 'aime_commentaire', 'cible' => 'commentaire_actualite_aime', 'colonne' => 'commentaire_actualite_id'],
            ['source' => 'aime_commentaire_jeu', 'cible' => 'commentaire_jeu_aime', 'colonne' => 'commentaire_jeu_id'],
        ];
        $resultats = [];

        foreach ($configurations as $configuration) {
            $reactions = $legacy->fetchAllAssociative(sprintf(
                'SELECT DISTINCT id_commentaire, id_pseudo_utilisateur_qui_aime AS id_utilisateur FROM %s',
                $configuration['source'],
            ));

            foreach ($reactions as $reaction) {
                $this->connection->executeStatement(
                    sprintf('INSERT IGNORE INTO %s (%s, utilisateur_id) VALUES (:commentaire, :utilisateur)', $configuration['cible'], $configuration['colonne']),
                    ['commentaire' => (int) $reaction['id_commentaire'], 'utilisateur' => (int) $reaction['id_utilisateur']],
                );
            }

            $resultats[] = [$configuration['source'], count($reactions)];
        }

        $io->table(['Source', 'Réactions uniques'], $resultats);
        $io->success('Les réactions de l’ancien site ont été importées sans doublons.');

        return Command::SUCCESS;
    }
}
