<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Service\ImageProfilUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:gerer-comptes-inactifs',
    description: 'Avertit après 23 mois, programme après 24 mois puis supprime les comptes après un dernier délai de 30 jours.',
)]
final class GererComptesInactifsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly ImageProfilUploader $imagesProfil,
        #[Autowire('%env(MAILER_FROM)%')] private readonly string $expediteur,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les actions sans envoyer de message ni modifier les comptes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $simulation = (bool) $input->getOption('dry-run');
        $maintenant = new \DateTimeImmutable();
        $limitePremierAvertissement = $maintenant->modify('-23 months');
        $limiteProgrammation = $maintenant->modify('-24 months');
        $delaiEntreAvertissements = $maintenant->modify('-30 days');
        $limiteSuppression = $maintenant->modify('-30 days');
        $statistiques = ['avertis' => 0, 'programmes' => 0, 'reactives' => 0, 'supprimes' => 0, 'ignores' => 0, 'erreurs' => 0];

        /** @var list<Utilisateur> $utilisateurs */
        $utilisateurs = $this->entityManager->getRepository(Utilisateur::class)->findAll();
        foreach ($utilisateurs as $utilisateur) {
            if (in_array('ROLE_ADMIN', $utilisateur->getRoles(), true)) {
                ++$statistiques['ignores'];
                continue;
            }

            $derniereActivite = $utilisateur->getDerniereActivite() ?? $utilisateur->getInscritLe();
            $avertiLe = $utilisateur->getInactiviteAvertieLe();
            $programmeeLe = $utilisateur->getSuppressionProgrammeeLe();

            if (($avertiLe && $derniereActivite >= $avertiLe) || ($programmeeLe && $derniereActivite >= $programmeeLe)) {
                ++$statistiques['reactives'];
                if (!$simulation) {
                    $utilisateur->annulerSuppressionPourInactivite();
                }
                continue;
            }

            if ($programmeeLe !== null && $programmeeLe <= $limiteSuppression) {
                ++$statistiques['supprimes'];
                $io->writeln(sprintf('Suppression : #%d %s', $utilisateur->getId(), $utilisateur->getPseudo()));
                if (!$simulation) {
                    $this->entityManager->remove($utilisateur);
                    $this->entityManager->flush();
                    $this->imagesProfil->supprimerMedias($utilisateur);
                }
                continue;
            }

            if ($programmeeLe === null && $avertiLe !== null && $avertiLe <= $delaiEntreAvertissements && $derniereActivite <= $limiteProgrammation) {
                if ($this->envoyerAvertissement($utilisateur, true, $simulation, $io)) {
                    ++$statistiques['programmes'];
                    if (!$simulation) {
                        $utilisateur->setSuppressionProgrammeeLe($maintenant);
                    }
                } else {
                    ++$statistiques['erreurs'];
                }
                continue;
            }

            if ($avertiLe === null && $derniereActivite <= $limitePremierAvertissement) {
                if ($this->envoyerAvertissement($utilisateur, false, $simulation, $io)) {
                    ++$statistiques['avertis'];
                    if (!$simulation) {
                        $utilisateur->setInactiviteAvertieLe($maintenant);
                    }
                } else {
                    ++$statistiques['erreurs'];
                }
            }
        }

        if (!$simulation) {
            $this->entityManager->flush();
        }

        $io->table(['Action', 'Nombre'], [
            ['Premiers avertissements', $statistiques['avertis']],
            ['Suppressions programmées', $statistiques['programmes']],
            ['Procédures annulées après retour', $statistiques['reactives']],
            ['Comptes supprimés', $statistiques['supprimes']],
            ['Administrateurs ignorés', $statistiques['ignores']],
            ['Erreurs d’envoi', $statistiques['erreurs']],
        ]);
        $io->success($simulation ? 'Simulation terminée : aucune donnée modifiée.' : 'Traitement des comptes inactifs terminé.');

        return $statistiques['erreurs'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function envoyerAvertissement(Utilisateur $utilisateur, bool $suppressionProgrammee, bool $simulation, SymfonyStyle $io): bool
    {
        $email = $utilisateur->getEmail();
        $adresseUtilisable = $email !== null && !str_ends_with($email, '@oauth.glitchworlds.local');
        $etape = $suppressionProgrammee ? 'dernier avertissement' : 'premier avertissement';
        $io->writeln(sprintf('%s : #%d %s%s', ucfirst($etape), $utilisateur->getId(), $utilisateur->getPseudo(), $adresseUtilisable ? '' : ' (sans e-mail utilisable)'));

        if ($simulation || !$adresseUtilisable) {
            return true;
        }

        $sujet = $suppressionProgrammee
            ? 'Ton compte Glitchworlds sera supprimé dans 30 jours'
            : 'Ton compte Glitchworlds est inactif';
        $message = $suppressionProgrammee
            ? "Bonjour {$utilisateur->getPseudo()},\n\nTon compte est inactif depuis au moins 24 mois. Sa suppression est maintenant programmée dans 30 jours. Connecte-toi avant cette échéance pour l’annuler automatiquement.\n\nL’équipe Glitchworlds"
            : "Bonjour {$utilisateur->getPseudo()},\n\nTon compte Glitchworlds est inactif depuis 23 mois. Sans nouvelle connexion, sa suppression sera programmée à partir de 24 mois d’inactivité. Une simple connexion suffit à conserver ton compte.\n\nL’équipe Glitchworlds";

        try {
            $this->mailer->send((new Email())->from($this->expediteur)->to($email)->subject($sujet)->text($message));
            return true;
        } catch (\Throwable $erreur) {
            $io->error(sprintf('Échec de l’envoi à %s : %s', $email, $erreur->getMessage()));
            return false;
        }
    }
}
