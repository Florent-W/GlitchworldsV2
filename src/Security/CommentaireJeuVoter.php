<?php

namespace App\Security;

use App\Entity\CommentaireJeu;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

final class CommentaireJeuVoter extends Voter
{
    public const SUPPRIMER = 'COMMENTAIRE_SUPPRIMER';
    public const MODIFIER = 'COMMENTAIRE_MODIFIER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::SUPPRIMER, self::MODIFIER], true)
            && $subject instanceof CommentaireJeu;
    }

    /** @param CommentaireJeu $subject */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();

        return $utilisateur instanceof Utilisateur
            && $subject->getAuteur() === $utilisateur;
    }
}
