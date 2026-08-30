<?php

namespace App\Enum;

enum CategorieActualite: string
{
    case News = 'news';
    case Glitchs = 'glitchs';
    case Mods = 'mods';
    case Tutoriels = 'tutoriels';

    public function label(): string
    {
        return match ($this) {
            self::News => 'News',
            self::Glitchs => 'Glitchs',
            self::Mods => 'Mods',
            self::Tutoriels => 'Tutoriels',
        };
    }
}
