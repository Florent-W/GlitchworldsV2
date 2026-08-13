<?php

namespace App\Service;

final class BbcodeConverter
{
    public function toHtml(string $contenu, bool $avecLien = true): string
    {
        $contenu = htmlspecialchars($contenu, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contenu = nl2br($contenu, false);
        $contenu = $this->remplacerBalises($contenu, $avecLien);

        return $contenu;
    }

    public function toText(string $contenu): string
    {
        $contenu = $this->supprimerBalises($contenu);
        $contenu = html_entity_decode($contenu, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contenu = preg_replace('/\s+/u', ' ', $contenu) ?? $contenu;

        return trim($contenu);
    }

    private function remplacerBalises(string $contenu, bool $avecLien): string
    {
        $remplacements = [
            '[i]' => '<em>',
            '[/i]' => '</em>',
            '[b]' => '<strong>',
            '[/b]' => '</strong>',
            '[u]' => '<u>',
            '[/u]' => '</u>',
            '[citation]' => '<blockquote class="blockquote">',
            '[/citation]' => '</blockquote>',
            '[center]' => '<div class="text-center">',
            '[/center]' => '</div>',
            '[gauche]' => '<div class="text-start">',
            '[/gauche]' => '</div>',
            '[droite]' => '<div class="text-end">',
            '[/droite]' => '</div>',
            '[liste]' => '<ul>',
            '[/liste]' => '</ul>',
            '[elementliste]' => '<li>',
            '[/elementliste]' => '</li>',
            '[Tableau]' => '<table class="table table-striped table-bordered">',
            '[/Tableau]' => '</table>',
            '[TableauDebut]' => '<thead>',
            '[/TableauDebut]' => '</thead>',
            '[TableauCorps]' => '<tbody>',
            '[/TableauCorps]' => '</tbody>',
            '[TableauLigne]' => '<tr>',
            '[/TableauLigne]' => '</tr>',
            '[TableauColonne]' => '<td>',
            '[/TableauColonne]' => '</td>',
            '[TableauEntréeColonne]' => '<th scope="col">',
            '[/TableauEntréeColonne]' => '</th>',
            '[TableauEntréeLigne]' => '<th scope="row">',
            '[/TableauEntréeLigne]' => '</th>',
        ];

        $contenu = str_replace(array_keys($remplacements), array_values($remplacements), $contenu);

        $contenu = preg_replace('#\[gaucheFlottant](.*?)\[/gaucheFlottant]#s', '<div class="text-start">$1</div>', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[droiteFlottant](.*?)\[/droiteFlottant]#s', '<div class="text-end">$1</div>', $contenu) ?? $contenu;

        $contenu = preg_replace(
            '#\[image2=(.+?),([1-9]\d{0,2})\](.+?)\[/image2]#s',
            '<img class="img-fluid" style="float: $1; max-height: $2px; max-width: $2px;" src="/images/$3" alt="">',
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace(
            '#\[image2=(.+?)](.+?)\[/image2]#s',
            '<img class="img-fluid" style="float: $1; max-height: 900px;" src="/images/$2" alt="">',
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace('#\[image](.+?)\[/image]#s', '<img class="img-fluid" src="$1" alt="">', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[icone=(.+?)]\[/icone]#s', '<img class="img-fluid" src="/icones/$1" alt="">', $contenu) ?? $contenu;

        if ($avecLien) {
            $contenu = preg_replace(
                '#\[lien](.+?)\[/lien]\[texteLien](.*?)\[/texteLien]#s',
                '<a href="$1" rel="noopener noreferrer">$2</a>',
                $contenu
            ) ?? $contenu;
            $contenu = str_replace(['[lien]', '[/lien]', '[texteLien]', '[/texteLien]'], ['<a href="', '">', '', '</a>'], $contenu);
        } else {
            $contenu = preg_replace('#\[lien].*?\[/lien]#s', '', $contenu) ?? $contenu;
            $contenu = str_replace(['[texteLien]', '[/texteLien]'], '', $contenu);
        }

        $contenu = preg_replace(
            '#\[video](.+?)\[/video]#s',
            '<iframe width="640" height="360" class="ratio ratio-16x9" src="$1" title="Vidéo" allowfullscreen loading="lazy"></iframe>',
            $contenu
        ) ?? $contenu;
        $contenu = str_replace('https://www.youtube.com/watch?v=', 'https://www.youtube.com/embed/', $contenu);

        $contenu = preg_replace('#\[taille=(.+?)]#', '<span style="font-size: $1;">', $contenu) ?? $contenu;
        $contenu = str_replace('[/taille]', '</span>', $contenu);

        $contenu = preg_replace('#\[titre=(.+?)]#', '<div class="$1">', $contenu) ?? $contenu;
        $contenu = str_replace('[/titre]', '</div>', $contenu);

        $contenu = preg_replace('#\[couleur=(.+?)]#', '<span style="color: $1;">', $contenu) ?? $contenu;
        $contenu = str_replace('[/couleur]', '</span>', $contenu);

        $contenu = preg_replace('#\[couleurfond=(.+?)]#', '<span style="background-color: $1;">', $contenu) ?? $contenu;
        $contenu = str_replace('[/couleurfond]', '</span>', $contenu);

        $contenu = preg_replace('#\[TableauEntrée](.*?)\[/TableauEntrée]#s', '<div>$1</div>', $contenu) ?? $contenu;

        return $contenu;
    }

    private function supprimerBalises(string $contenu): string
    {
        $contenu = preg_replace('#\[lien].*?\[/lien]#s', '', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[image2=.*?].*?\[/image2]#s', '', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[image].*?\[/image]#s', '', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[icone=.*?]?\[/icone]#s', '', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[video].*?\[/video]#s', '', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[/?[^\]]+]#', '', $contenu) ?? $contenu;

        return $contenu;
    }
}
