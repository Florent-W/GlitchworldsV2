<?php

namespace App\Tests\Security;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Security\PropositionJeuVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PropositionJeuVoterTest extends TestCase
{
    public function testSeulLeCreateurPeutModifierUnePropositionEnAttente(): void
    {
        $createur = (new Utilisateur())->setPseudo('Créateur');
        $autre = (new Utilisateur())->setPseudo('Autre');
        $jeu = (new Jeu())->setCreateur($createur)->setStatut(StatutJeu::EnAttente);
        $voter = new PropositionJeuVoter();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(new UsernamePasswordToken($createur, 'main'), $jeu, [PropositionJeuVoter::MODIFIER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new UsernamePasswordToken($autre, 'main'), $jeu, [PropositionJeuVoter::MODIFIER]));

        $jeu->setStatut(StatutJeu::Approuve);
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new UsernamePasswordToken($createur, 'main'), $jeu, [PropositionJeuVoter::MODIFIER]));
    }

    public function testSeulUnAdministrateurPeutModifierUneFicheApprouvee(): void
    {
        $administrateur = (new Utilisateur())->setPseudo('Administrateur')->setRoles(['ROLE_ADMIN']);
        $moderateur = (new Utilisateur())->setPseudo('Modérateur')->setRoles(['ROLE_MODERATEUR']);
        $jeu = (new Jeu())->setStatut(StatutJeu::Approuve);
        $voter = new PropositionJeuVoter();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(new UsernamePasswordToken($administrateur, 'main'), $jeu, [PropositionJeuVoter::MODIFIER]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(new UsernamePasswordToken($moderateur, 'main'), $jeu, [PropositionJeuVoter::MODIFIER]));
    }
}
