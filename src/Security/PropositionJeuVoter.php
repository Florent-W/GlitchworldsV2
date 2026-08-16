<?php

namespace App\Security;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PropositionJeuVoter extends Voter
{
    public const MODIFIER = 'PROPOSITION_JEU_MODIFIER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MODIFIER && $subject instanceof Jeu;
    }

    /** @param Jeu $subject */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $utilisateur->getRoles(), true)) {
            return true;
        }

        return $subject->getCreateur() === $utilisateur
            && $subject->getStatut() === StatutJeu::EnAttente;
    }
}
