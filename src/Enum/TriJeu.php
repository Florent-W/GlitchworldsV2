<?php

namespace App\Enum;

enum TriJeu: string
{
    case IdDesc = 'id-desc';
    case IdAsc = 'id-asc';
    case Recent = 'recent';
    case Ancien = 'ancien';
    case Nom = 'nom';
    case Populaire = 'populaire';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Recent => 'Date de sortie récente',
            self::Nom => 'Nom A-Z',
            self::Ancien => 'Date de sortie ancienne',
            self::IdDesc => 'Plus récent',
            self::IdAsc => 'Plus ancien',
            self::Populaire => 'Plus populaires',
            self::Note => 'Mieux notés',
        };
    }
}
