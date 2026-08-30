<?php

namespace App\Command;

use App\Service\RetroProgressionLegacy;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:retro-attribuer-progression',
    description: 'Rattrape XP, points et succès à partir des données importées du legacy.',
)]
final class RetroAttribuerProgressionCommand extends Command
{
    public function __construct(private readonly RetroProgressionLegacy $retroProgressionLegacy)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Estime les crédits sans écrire en base')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'Limite le traitement à un membre')
            ->addOption('skip-legacy-sync', null, InputOption::VALUE_NONE, 'Ne resynchronise pas les auteurs avis / créateurs jeux depuis le legacy')
            ->addOption('notifier', null, InputOption::VALUE_NONE, 'Envoie une notification à chaque succès débloqué');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $skipSync = (bool) $input->getOption('skip-legacy-sync');
        $notifier = (bool) $input->getOption('notifier');
        $userId = $input->getOption('user-id');
        $utilisateurId = $userId !== null ? (int) $userId : null;

        if ($dryRun) {
            $io->note('Mode dry-run : aucune écriture en base.');
        }

        if (!$skipSync && !$dryRun) {
            $sync = $this->retroProgressionLegacy->synchroniserDonneesLegacy();
            $io->writeln(sprintf(
                'Synchronisation legacy : %d auteur(s) avis corrigé(s), %d créateur(s) jeu corrigé(s).',
                $sync['avis'],
                $sync['createurs'],
            ));
        } elseif (!$skipSync) {
            $io->writeln('Synchronisation legacy ignorée en dry-run (relancer sans --dry-run pour corriger avis/créateurs).');
        }

        $stats = $this->retroProgressionLegacy->attribuer($utilisateurId, $dryRun, $notifier);

        $io->table(['Statistique', 'Valeur'], [
            ['Membres traités', $stats['membres']],
            ['Commentaires jeu crédités', $stats['commentaires_jeu']],
            ['Commentaires actualité crédités', $stats['commentaires_actualite']],
            ['Notes créditées', $stats['notes']],
            ['Favoris crédités', $stats['favoris']],
            ['Publications créditées', $stats['publications']],
            ['Jeux approuvés crédités', $stats['jeux_approuves']],
            ['Ancienneté créditée', $stats['anciennete']],
            ['Succès débloqués', $stats['succes']],
            ['XP totale estimée/ajoutée', $stats['experience']],
            ['Points totaux estimés/ajoutés', $stats['points']],
        ]);

        $io->success($dryRun ? 'Estimation terminée.' : 'Rétro-attribution terminée.');

        return Command::SUCCESS;
    }
}
