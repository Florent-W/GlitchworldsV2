<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TexteExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('extrait', $this->extrait(...)),
        ];
    }

    public function extrait(?string $texte, int $longueur = 160): string
    {
        $texte = trim((string) preg_replace('/\s+/u', ' ', (string) $texte));

        if ($longueur < 1 || mb_strlen($texte) <= $longueur) {
            return $texte;
        }

        $extrait = mb_substr($texte, 0, $longueur);
        $dernierEspace = mb_strrpos($extrait, ' ');

        if ($dernierEspace !== false) {
            $extrait = mb_substr($extrait, 0, $dernierEspace);
        }

        return rtrim($extrait, " \t\n\r\0\x0B.,;:!?").'…';
    }
}
