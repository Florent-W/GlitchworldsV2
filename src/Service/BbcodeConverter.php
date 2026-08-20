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
        $contenu = preg_replace_callback(
            '#\[gallery\](.*?)\[/gallery\]#s',
            fn (array $correspondance): string => '<div class="gw-bbcode-gallery gallery">'.$this->convertirContenuGalerie($correspondance[1]).'</div>',
            $contenu
        ) ?? $contenu;

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
            '[section]' => '<section class="gw-bbcode-section">',
            '[/section]' => '</section>',
        ];

        $contenu = str_replace(array_keys($remplacements), array_values($remplacements), $contenu);

        $contenu = preg_replace('#\[gaucheFlottant](.*?)\[/gaucheFlottant]#s', '<div class="gw-bbcode-float gw-bbcode-float--left">$1</div>', $contenu) ?? $contenu;
        $contenu = preg_replace('#\[droiteFlottant](.*?)\[/droiteFlottant]#s', '<div class="gw-bbcode-float gw-bbcode-float--right">$1</div>', $contenu) ?? $contenu;

        $contenu = preg_replace_callback(
            '#\[image2=([^,\]]+),([1-9]\d{0,2})\](.+?)\[/image2]#s',
            fn (array $correspondance): string => $this->imageInline($correspondance[1], $correspondance[3], (int) $correspondance[2]),
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace_callback(
            '#\[image2=([^,\]]+)\](.+?)\[/image2]#s',
            fn (array $correspondance): string => $this->imageInline($correspondance[1], $correspondance[2]),
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
            fn (array $correspondance): string => sprintf(
                '<div class="gw-bbcode-video ratio ratio-16x9"><iframe src="%s" title="Vidéo" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe></div>',
                $this->securiserVideo($correspondance[1]),
            ),
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

    private function convertirContenuGalerie(string $contenu): string
    {
        $contenu = preg_replace_callback(
            '#\[image2=([^,\]]+),([1-9]\d{0,2})\](.+?)\[/image2]#s',
            fn (array $correspondance): string => $this->imageGalerie($correspondance[1], $correspondance[3], (int) $correspondance[2]),
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace_callback(
            '#\[image2=([^,\]]+)\](.+?)\[/image2]#s',
            fn (array $correspondance): string => $this->imageGalerie($correspondance[1], $correspondance[2]),
            $contenu
        ) ?? $contenu;
        $contenu = preg_replace_callback(
            '#\[image](.+?)\[/image]#s',
            fn (array $correspondance): string => $this->imageGalerieUrl($correspondance[1]),
            $contenu
        ) ?? $contenu;

        return $contenu;
    }

    private function imageInline(string $alignement, string $fichier, ?int $tailleMax = null): string
    {
        $fichier = trim(html_entity_decode($fichier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        if (
            '' === $fichier
            || str_contains($fichier, '..')
            || !preg_match('/\.(?:avif|gif|jpe?g|png|webp)$/i', $fichier)
        ) {
            return '';
        }

        $url = htmlspecialchars('/images/'.$fichier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $style = $this->styleImageInline($alignement, $tailleMax);

        return sprintf(
            '<img class="img-fluid" style="%s" src="%s" alt="" loading="lazy" decoding="async">',
            $style,
            $url,
        );
    }

    private function imageGalerie(string $alignement, string $fichier, ?int $tailleMax = null): string
    {
        $fichier = trim(html_entity_decode($fichier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        if (
            '' === $fichier
            || str_contains($fichier, '..')
            || !preg_match('/\.(?:avif|gif|jpe?g|png|webp)$/i', $fichier)
        ) {
            return '';
        }

        $url = htmlspecialchars('/images/'.$fichier, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $style = $this->styleImageInline($alignement, $tailleMax);

        return sprintf(
            '<div class="gw-lg-item" data-src="%1$s" data-responsive-src="%1$s" style="%2$s"><a href="#%3$s" onclick="return false;"><img class="img-fluid mw-100" src="%1$s" alt="" loading="lazy" decoding="async"></a></div>',
            $url,
            $style,
            htmlspecialchars(basename($fichier), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    private function imageGalerieUrl(string $url): string
    {
        $url = $this->securiserUrl($url);
        if ('#' === $url) {
            return '';
        }

        return sprintf(
            '<div class="gw-lg-item" data-src="%1$s" data-responsive-src="%1$s"><a href="%1$s"><img class="img-fluid mw-100" src="%1$s" alt="" loading="lazy" decoding="async"></a></div>',
            $url,
        );
    }

    private function styleImageInline(string $alignement, ?int $tailleMax = null): string
    {
        $alignement = mb_strtolower(trim(html_entity_decode($alignement, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')));
        $style = 'display:inline-block; margin-block:10px;';
        if ('none' !== $alignement && '' !== $alignement) {
            $style .= ' float: '.htmlspecialchars($alignement, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').';';
        }
        if (null !== $tailleMax) {
            $style .= sprintf(' max-height:%dpx; max-width:%dpx;', $tailleMax, $tailleMax);
        } else {
            $style .= ' max-height:900px;';
        }

        return $style;
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
