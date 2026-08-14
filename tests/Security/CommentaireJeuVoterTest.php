<?php

namespace App\Tests\Security;

use App\Entity\CommentaireJeu;
use App\Entity\Utilisateur;
use App\Security\CommentaireJeuVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class CommentaireJeuVoterTest extends TestCase
{
    public function testAuteurAutoriseEtAutreMembreRefuse(): void
    {
        $auteur = (new Utilisateur())->setPseudo('Auteur');
        $autreMembre = (new Utilisateur())->setPseudo('Autre');
        $commentaire = (new CommentaireJeu())->setAuteur($auteur);
        $voter = new CommentaireJeuVoter();

        foreach ([CommentaireJeuVoter::MODIFIER, CommentaireJeuVoter::SUPPRIMER] as $permission) {
            self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote(
                new UsernamePasswordToken($auteur, 'main', $auteur->getRoles()),
                $commentaire,
                [$permission],
            ));
            self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote(
                new UsernamePasswordToken($autreMembre, 'main', $autreMembre->getRoles()),
                $commentaire,
                [$permission],
            ));
        }
    }
}
