<?php

namespace App\Enum;

enum StatutActualite: string
{
    case Brouillon = 'brouillon';
    case EnAttente = 'en_attente';
    case Publiee = 'publiee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::EnAttente => 'En attente',
            self::Publiee => 'Publiée',
        };
    }
}
