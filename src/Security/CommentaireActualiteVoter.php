<?php

namespace App\Security;

use App\Entity\CommentaireActualite;
use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

final class CommentaireActualiteVoter extends Voter
{
    public const MODIFIER = 'COMMENTAIRE_ACTUALITE_MODIFIER';
    public const SUPPRIMER = 'COMMENTAIRE_ACTUALITE_SUPPRIMER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof CommentaireActualite
            && in_array($attribute, [self::MODIFIER, self::SUPPRIMER], true);
    }

    /** @param CommentaireActualite $subject */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilisateur = $token->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return false;
        }

        if ($subject->getAuteur() === $utilisateur) {
            return true;
        }

        return array_intersect(['ROLE_MODERATEUR', 'ROLE_ADMIN'], $utilisateur->getRoles()) !== [];
    }
}
