<?php

namespace App\Enum;

enum StatutActualite: string
{
    case Brouillon = 'brouillon';
    case EnAttente = 'en_attente';
    case Publiee = 'publiee';
}
