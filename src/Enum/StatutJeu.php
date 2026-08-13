<?php

namespace App\Enum;

enum StatutJeu: string
{
    case Brouillon = 'brouillon';
    case EnAttente = 'en_attente';
    case Approuve = 'approuve';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::EnAttente => 'En attente',
            self::Approuve => 'Approuvé',
            self::Refuse => 'Refusé',
        };
    }
}
