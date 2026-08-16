<?php

namespace App\Enum;

enum TypeArticleBoutique: string
{
    case Badge = 'badge';
    case Titre = 'titre';

    public function label(): string { return match ($this) { self::Badge => 'Badge de profil', self::Titre => 'Titre de profil' }; }
}
