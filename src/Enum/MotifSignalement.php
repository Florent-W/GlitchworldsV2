<?php

namespace App\Enum;

enum MotifSignalement: string
{
    case Spam = 'spam';
    case Insulte = 'insulte';
    case ContenuIllegal = 'contenu_illegal';
    case FaussesInformations = 'fausses_informations';
    case DroitsAuteur = 'droits_auteur';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam ou publicité',
            self::Insulte => 'Harcèlement ou propos insultants',
            self::ContenuIllegal => 'Contenu illégal ou dangereux',
            self::FaussesInformations => 'Informations trompeuses',
            self::DroitsAuteur => 'Atteinte aux droits d’auteur',
            self::Autre => 'Autre raison',
        };
    }
}
