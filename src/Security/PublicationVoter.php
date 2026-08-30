<?php

namespace App\Security;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PublicationVoter extends Voter
{
    public const MODIFIER = 'PUBLICATION_MODIFIER';
    public const SUPPRIMER = 'PUBLICATION_SUPPRIMER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Publication
            && in_array($attribute, [self::MODIFIER, self::SUPPRIMER], true);
    }

    /** @param Publication $subject */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }
        if ($subject->getAuteur() === $utilisateur) {
            return true;
        }

        return self::SUPPRIMER === $attribute
            && array_intersect(['ROLE_MODERATEUR', 'ROLE_ADMIN'], $utilisateur->getRoles()) !== [];
    }
}
