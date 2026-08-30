<?php

namespace App\Security;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Enum\StatutActualite;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PropositionActualiteVoter extends Voter
{
    public const MODIFIER = 'PROPOSITION_ACTUALITE_MODIFIER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MODIFIER && $subject instanceof Actualite;
    }

    /** @param Actualite $subject */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $utilisateur->getRoles(), true)) {
            return true;
        }

        return $subject->getAuteur() === $utilisateur
            && in_array($subject->getStatut(), [StatutActualite::Brouillon, StatutActualite::EnAttente], true);
    }
}
