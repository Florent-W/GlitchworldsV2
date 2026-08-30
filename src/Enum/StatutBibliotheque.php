<?php

namespace App\Enum;

enum StatutBibliotheque: string
{
    case A_Jouer = 'a_jouer';
    case En_Cours = 'en_cours';
    case Termine = 'termine';

    public function label(): string { return match ($this) { self::A_Jouer => 'À jouer', self::En_Cours => 'En cours', self::Termine => 'Terminé' }; }
    public function icone(): string { return match ($this) { self::A_Jouer => 'bookmark-plus', self::En_Cours => 'play-circle', self::Termine => 'check-circle' }; }
}
