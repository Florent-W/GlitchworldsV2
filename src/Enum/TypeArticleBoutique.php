<?php

namespace App\Enum;

enum TypeArticleBoutique: string
{
    case Badge = 'badge';
    case Titre = 'titre';
    case Effet = 'effet';
    case Cadre = 'cadre';
    case Vitrine = 'vitrine';

    public function label(): string { return match ($this) { self::Badge => 'Badge de profil', self::Titre => 'Titre de profil', self::Effet => 'Effet de profil', self::Cadre => 'Cadre d’avatar', self::Vitrine => 'Vitrine de créateur' }; }
}
