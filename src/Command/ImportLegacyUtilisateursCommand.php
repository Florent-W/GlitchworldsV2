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
    name: 'app:import-legacy-utilisateurs',
    description: 'Complète les comptes importés avec leur e-mail, mot de passe et rôle legacy.',
)]
final class ImportLegacyUtilisateursCommand extends Command
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
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse les comptes sans modifier la base V2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->legacyDatabaseUrl) {
            $io->error('Variable LEGACY_DATABASE_URL manquante dans .env.local');

            return Command::FAILURE;
        }

        $parser = new DsnParser(['mysql' => 'pdo_mysql', 'mysqli' => 'mysqli']);
        $legacy = DriverManager::getConnection($parser->parse($this->legacyDatabaseUrl));
        $utilisateurs = $legacy->fetchAllAssociative(
            'SELECT id, pseudo, mail, mdp, statut FROM utilisateurs ORDER BY id ASC'
        );

        $occurrencesEmails = [];
        $occurrencesPseudos = [];
        foreach ($utilisateurs as $utilisateur) {
            $email = $this->normaliserEmail((string) $utilisateur['mail']);
            if ($email !== null) {
                $occurrencesEmails[$email] = ($occurrencesEmails[$email] ?? 0) + 1;
            }

            $pseudo = $this->normaliserPseudo((string) $utilisateur['pseudo']);
            if ($pseudo !== null) {
                $occurrencesPseudos[$pseudo] = ($occurrencesPseudos[$pseudo] ?? 0) + 1;
            }
        }

        $proprietairesEmails = [];
        foreach ($this->connection->fetchAllAssociative('SELECT id, email FROM utilisateur WHERE email IS NOT NULL') as $utilisateur) {
            $proprietairesEmails[(string) $utilisateur['email']] = (int) $utilisateur['id'];
        }

        $statistiques = [
            'comptes' => \count($utilisateurs),
            'emails' => 0,
            'emails_doublons' => 0,
            'emails_invalides' => 0,
            'emails_indisponibles' => 0,
            'mots_de_passe' => 0,
            'administrateurs' => 0,
            'absents' => 0,
            'pseudos_doublons' => 0,
            'inaccessibles' => 0,
        ];

        $this->connection->beginTransaction();

        try {
            foreach ($utilisateurs as $utilisateur) {
                $id = (int) $utilisateur['id'];
                if (!$this->connection->fetchOne('SELECT 1 FROM utilisateur WHERE id = ?', [$id])) {
                    ++$statistiques['absents'];
                    continue;
                }

                $email = $this->normaliserEmail((string) $utilisateur['mail']);
                $emailImportable = $email !== null && 1 === $occurrencesEmails[$email];

                if ($email === null) {
                    ++$statistiques['emails_invalides'];
                } elseif (!$emailImportable) {
                    ++$statistiques['emails_doublons'];
                } elseif (isset($proprietairesEmails[$email]) && $proprietairesEmails[$email] !== $id) {
                    ++$statistiques['emails_indisponibles'];
                    $emailImportable = false;
                } else {
                    ++$statistiques['emails'];
                }

                $pseudo = $this->normaliserPseudo((string) $utilisateur['pseudo']);
                $pseudoUnique = $pseudo !== null && 1 === $occurrencesPseudos[$pseudo];
                if (!$pseudoUnique) {
                    ++$statistiques['pseudos_doublons'];
                }
                if (!$emailImportable && !$pseudoUnique) {
                    ++$statistiques['inaccessibles'];
                }

                $motDePasse = trim((string) $utilisateur['mdp']);
                $motDePasseImportable = 1 === preg_match('/^\$2[aby]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $motDePasse);
                if ($motDePasseImportable) {
                    ++$statistiques['mots_de_passe'];
                }

                $estAdministrateur = 'administrateur' === mb_strtolower(trim((string) $utilisateur['statut']));
                if ($estAdministrateur) {
                    ++$statistiques['administrateurs'];
                }

                $champs = ['roles' => json_encode($estAdministrateur ? ['ROLE_ADMIN'] : [], JSON_THROW_ON_ERROR)];
                if ($emailImportable) {
                    $champs['email'] = $email;
                }
                if ($motDePasseImportable) {
                    $champs['mot_de_passe'] = $motDePasse;
                }

                if (!(bool) $input->getOption('dry-run')) {
                    $this->connection->update('utilisateur', $champs, ['id' => $id]);
                }
            }

            if ((bool) $input->getOption('dry-run')) {
                $this->connection->rollBack();
            } else {
                $this->connection->commit();
            }
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $io->table(['Résultat', 'Nombre'], [
            ['Comptes legacy analysés', $statistiques['comptes']],
            ['E-mails importables', $statistiques['emails']],
            ['E-mails dupliqués ignorés', $statistiques['emails_doublons']],
            ['E-mails invalides ou vides ignorés', $statistiques['emails_invalides']],
            ['E-mails déjà pris dans la V2', $statistiques['emails_indisponibles']],
            ['Mots de passe bcrypt importables', $statistiques['mots_de_passe']],
            ['Administrateurs', $statistiques['administrateurs']],
            ['Comptes absents de la V2', $statistiques['absents']],
            ['Comptes avec un pseudo dupliqué', $statistiques['pseudos_doublons']],
            ['Comptes sans identifiant unique', $statistiques['inaccessibles']],
        ]);

        $message = (bool) $input->getOption('dry-run')
            ? 'Analyse terminée, aucune donnée modifiée.'
            : 'Les comptes legacy ont été complétés sans réimporter les jeux.';
        $io->success($message);

        return Command::SUCCESS;
    }

    private function normaliserEmail(string $email): ?string
    {
        $email = mb_strtolower(trim($email));

        return false !== filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normaliserPseudo(string $pseudo): ?string
    {
        $pseudo = mb_strtolower(trim($pseudo));

        return '' !== $pseudo ? $pseudo : null;
    }
}
