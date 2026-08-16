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
            '[gallery]' => '<div class="gw-bbcode-gallery">',
            '[/gallery]' => '</div>',
            '[section]' => '<section class="gw-bbcode-section">',
            '[/section]' => '</section>',
        ];

        $contenu = str_replace(array_keys($remplacements), array_values($remplacements), $contenu);

        $contenu = preg_replace('#\[gaucheFlottant](.*?)\[/gaucheFlottant]#s', '<div class="gw-bbcode-float gw-bbcode-float--left">$1</div>', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[droiteFlottant](.*?)\[/droiteFlottant]#s', '<div class="gw-bbcode-float gw-bbcode-float--right">$1</div>', $contenu) ?? $contenu;

        $contenu = preg_replace(
            '#\[image2=(.+?),([1-9]\d{0,2})\](.+?)\[/image2]#s',
            '<img class="img-fluid" style="float: $1; max-height: $2px; max-width: $2px;" src="/images/$3" alt="" loading="lazy" decoding="async">',
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace(
            '#\[image2=(.+?)](.+?)\[/image2]#s',
            '<img class="img-fluid" style="float: $1; max-height: 900px;" src="/images/$2" alt="" loading="lazy" decoding="async">',
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace_callback(
            '#\[image](.+?)\[/image]#s',
            fn (array $correspondance): string => sprintf('<img class="img-fluid" src="%s" alt="" loading="lazy" decoding="async">', $this->securiserUrl($correspondance[1])),
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace_callback('#\[icone=(.+?)]\[/icone]#s', static function (array $correspondance): string {
            $fichier = html_entity_decode($correspondance[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return preg_match('/^[\pL\pN_.-]+$/u', $fichier) === 1 ? sprintf('<img class="gw-bbcode-icon" src="/icones/%s" alt="" loading="lazy" decoding="async">', htmlspecialchars($fichier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) : '';
        }, $contenu) ?? $contenu;

        if ($avecLien) {
            $contenu = preg_replace_callback(
                '#\[lien](.+?)\[/lien]\[texteLien](.*?)\[/texteLien]#s',
                fn (array $correspondance): string => sprintf('<a href="%s" rel="noopener noreferrer">%s</a>', $this->securiserUrl($correspondance[1], true), $correspondance[2]),
                $contenu
            ) ?? $contenu;
            $contenu = preg_replace_callback(
                '#\[lien](.+?)\[/lien]#s',
                fn (array $correspondance): string => sprintf('<a href="%1$s" rel="noopener noreferrer">%1$s</a>', $this->securiserUrl($correspondance[1], true)),
                $contenu
            ) ?? $contenu;
        } else {
            $contenu = preg_replace('#\[lien].*?\[/lien]#s', '', $contenu) ?? $contenu;
            $contenu = str_replace(['[texteLien]', '[/texteLien]'], '', $contenu);
        }

        $contenu = preg_replace_callback(
            '#\[video](.+?)\[/video]#s',
            fn (array $correspondance): string => sprintf('<iframe width="640" height="360" class="ratio ratio-16x9" src="%s" title="Vidéo" allowfullscreen loading="lazy"></iframe>', $this->securiserVideo($correspondance[1])),
            $contenu
        ) ?? $contenu;

        $tailles = ['smaller' => 'x-small', 'small' => 'small', 'medium' => 'medium', 'large' => 'large', 'largest' => 'x-large'];
        $contenu = preg_replace_callback('#\[taille=(.+?)](.*?)\[/taille]#s', static function (array $correspondance) use ($tailles): string {
            return sprintf('<span style="font-size: %s">%s</span>', $tailles[strtolower($correspondance[1])] ?? 'medium', $correspondance[2]);
        }, $contenu) ?? $contenu;
        $contenu = preg_replace_callback('#\[titre=(h[1-5])](.*?)\[/titre]#s', static fn (array $correspondance): string => sprintf('<%1$s>%2$s</%1$s>', $correspondance[1], $correspondance[2]), $contenu) ?? $contenu;

        $couleurs = ['blue', 'lightskyblue', 'yellow', 'green', 'orange', 'red', 'pink', 'violet', 'brown', 'silver'];
        $contenu = preg_replace_callback('#\[couleur=(.+?)](.*?)\[/couleur]#s', static function (array $correspondance) use ($couleurs): string {
            $couleur = in_array(strtolower($correspondance[1]), $couleurs, true) ? strtolower($correspondance[1]) : 'inherit';
            return sprintf('<span style="color: %s">%s</span>', $couleur, $correspondance[2]);
        }, $contenu) ?? $contenu;
        $contenu = preg_replace_callback('#\[couleurfond=(.+?)](.*?)\[/couleurfond]#s', static function (array $correspondance) use ($couleurs): string {
            $couleur = in_array(strtolower($correspondance[1]), $couleurs, true) ? strtolower($correspondance[1]) : 'transparent';
            return sprintf('<span style="background-color: %s">%s</span>', $couleur, $correspondance[2]);
        }, $contenu) ?? $contenu;

        $animations = ['fade-up', 'fade-down', 'fade-left', 'fade-right', 'flip-up', 'flip-down', 'flip-left', 'flip-right', 'zoom-in', 'zoom-out', 'zoom-in-right'];
        $contenu = preg_replace_callback('#\[animation=(.+?)](.*?)\[/animation]#s', static function (array $correspondance) use ($animations): string {
            $animation = in_array($correspondance[1], $animations, true) ? $correspondance[1] : 'fade-up';
            return sprintf('<div class="gw-bbcode-animation gw-bbcode-animation--%s">%s</div>', $animation, $correspondance[2]);
        }, $contenu) ?? $contenu;

        // Les anciennes sections pouvaient contenir couleur, image, vidéo ou dégradé.
        // On conserve leur contenu et leur structure sans réinjecter ces styles libres.
        $contenu = preg_replace('#\[section(?: [^\]]+)?](.*?)\[/section]#s', '<section class="gw-bbcode-section">$1</section>', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[slide(?: [^\]]+)?](.*?)\[/slide]#s', '<div class="gw-bbcode-slide">$1</div>', $contenu) ?? $contenu;

        $contenu = preg_replace('#\[TableauEntrée](.*?)\[/TableauEntrée]#s', '<div>$1</div>', $contenu) ?? $contenu;

        return $contenu;
    }

    private function securiserUrl(string $url, bool $autoriserEmail = false): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $schema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $estRelative = str_starts_with($url, '/') && !str_starts_with($url, '//');
        $estAncre = str_starts_with($url, '#');
        $schemasAutorises = $autoriserEmail ? ['http', 'https', 'mailto'] : ['http', 'https'];

        if (!$estRelative && !$estAncre && !in_array($schema, $schemasAutorises, true)) {
            return '#';
        }

        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function securiserVideo(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $url = str_replace('https://www.youtube.com/watch?v=', 'https://www.youtube.com/embed/', $url);
        $hote = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (!in_array($hote, ['youtube.com', 'www.youtube.com', 'youtu.be'], true)) {
            return '#';
        }

        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
