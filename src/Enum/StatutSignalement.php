<?php

namespace App\Enum;

enum StatutSignalement: string
{
    case EnAttente = 'en_attente';
    case Traite = 'traite';
    case Rejete = 'rejete';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Traite => 'Traité',
            self::Rejete => 'Rejeté',
        };
    }
}
