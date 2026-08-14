<?php

namespace App\Enum;

enum TriJeu: string
{
    case Recent = 'recent';
    case Nom = 'nom';
    case Ancien = 'ancien';

    public function label(): string
    {
        return match ($this) {
            self::Recent => 'Plus récents',
            self::Nom => 'Nom A-Z',
            self::Ancien => 'Plus anciens',
        };
    }
}
