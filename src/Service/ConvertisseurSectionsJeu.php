<?php

namespace App\Service;

final class ConvertisseurSectionsJeu
{
    /**
     * @return array{contenu: string, sections: int, raison: ?string}
     */
    public function convertir(string $contenu): array
    {
        if (preg_match('/\[section\s/i', $contenu) === 1) {
            return ['contenu' => $contenu, 'sections' => 0, 'raison' => 'deja_structure'];
        }

        $pattern = '#(?|\[center\]\s*\[titre=(?:h[1-4]|[1-4])\](.*?)\[/titre\]\s*\[/center\]|\[titre=(?:h[1-4]|[1-4])\](.*?)\[/titre\])#is';
        preg_match_all($pattern, $contenu, $titres, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if ($titres === []) {
            return ['contenu' => $contenu, 'sections' => 0, 'raison' => 'aucun_titre_principal'];
        }

        $blocs = [];
        $premierOffset = $titres[0][0][1];
        $introduction = trim(substr($contenu, 0, $premierOffset));
        $blocs[] = ['type' => 'presentation', 'titre' => 'Présentation générale', 'contenu' => $introduction];

        foreach ($titres as $index => $titre) {
            $blocComplet = $titre[0][0];
            $debutContenu = $titre[0][1] + strlen($blocComplet);
            $finContenu = isset($titres[$index + 1]) ? $titres[$index + 1][0][1] : strlen($contenu);
            $corps = trim(substr($contenu, $debutContenu, $finContenu - $debutContenu));
            $libelle = $this->nettoyerTitre($titre[1][0]);

            if ($libelle === '') {
                continue;
            }
            $blocs[] = ['type' => $this->typePourTitre($libelle), 'titre' => $libelle, 'contenu' => $corps];
        }

        $balisesActives = [];
        $sections = [];
        foreach ($blocs as $bloc) {
            $corps = $this->equilibrerBalisage($bloc['contenu'], $balisesActives);
            if ($corps === null) {
                return ['contenu' => $contenu, 'sections' => 0, 'raison' => 'balisage_invalide'];
            }
            if ($this->aDuContenu($corps)) {
                $sections[] = $this->encadrer($bloc['type'], $bloc['titre'], $corps);
            }
        }

        if ($balisesActives !== []) {
            return ['contenu' => $contenu, 'sections' => 0, 'raison' => 'balisage_invalide'];
        }

        if (count($sections) < 2) {
            return ['contenu' => $contenu, 'sections' => 0, 'raison' => 'moins_de_deux_sections'];
        }

        return ['contenu' => implode("\n\n", $sections), 'sections' => count($sections), 'raison' => null];
    }

    private function encadrer(string $type, string $titre, string $contenu): string
    {
        $titre = str_replace(['"', '[', ']'], ["'", '(', ')'], $titre);

        return sprintf('[section type=%s titre="%s"]%s%s%s[/section]', $type, $titre, "\n", trim($contenu), "\n");
    }

    private function nettoyerTitre(string $titre): string
    {
        $titre = preg_replace('#\[(?:icone|image2)(?:=[^\]]*)?\].*?\[/(?:icone|image2)\]#is', '', $titre) ?? $titre;
        $titre = preg_replace('#\[/?[^\]]+\]#', '', $titre) ?? $titre;
        $titre = html_entity_decode(strip_tags($titre), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $titre) ?? $titre, " \t\n\r\0\x0B:");
    }

    private function aDuContenu(string $contenu): bool
    {
        $visible = preg_replace('#\[/?[^\]]+\]#', '', $contenu) ?? $contenu;

        return trim($visible) !== '';
    }

    /**
     * Ferme à la fin du bloc puis rouvre au début du suivant les anciennes
     * balises de mise en page qui traversaient plusieurs titres.
     *
     * @param list<array{nom: string, ouverture: string}> $balisesActives
     */
    private function equilibrerBalisage(string $contenu, array &$balisesActives): ?string
    {
        $activesEntree = $balisesActives;
        preg_match_all(
            '#\[(\/)?(center|droite|gauche|liste|couleur|couleurfond)(?:=[^\]]*)?\]#i',
            $contenu,
            $balises,
            PREG_SET_ORDER,
        );

        foreach ($balises as $balise) {
            $nom = strtolower($balise[2]);
            if (($balise[1] ?? '') === '/') {
                $derniere = array_key_last($balisesActives);
                if ($derniere === null || $balisesActives[$derniere]['nom'] !== $nom) {
                    return null;
                }
                array_pop($balisesActives);
            } else {
                $balisesActives[] = ['nom' => $nom, 'ouverture' => $balise[0]];
            }
        }

        $prefixe = implode('', array_column($activesEntree, 'ouverture'));
        $fermetures = '';
        foreach (array_reverse($balisesActives) as $balise) {
            $fermetures .= '[/'.$balise['nom'].']';
        }

        return $prefixe.$contenu.$fermetures;
    }

    private function typePourTitre(string $titre): string
    {
        $normalise = strtolower(transliterator_transliterate('Any-Latin; Latin-ASCII', $titre));

        return match (true) {
            str_contains($normalise, 'telecharg'), str_contains($normalise, 'download'), str_contains($normalise, 'installation') => 'telechargement',
            str_contains($normalise, 'histoire'), str_contains($normalise, 'scenario'), str_contains($normalise, 'aventure') => 'histoire',
            str_contains($normalise, 'gameplay'), str_contains($normalise, 'jouabilite') => 'gameplay',
            str_contains($normalise, 'fonctionnalit'), str_contains($normalise, 'caracteristique') => 'fonctionnalites',
            str_contains($normalise, 'pokedex'), str_contains($normalise, 'bestiaire') => 'pokedex',
            str_contains($normalise, 'region'), str_contains($normalise, 'monde') => 'region',
            str_contains($normalise, 'personnage') => 'personnages',
            str_contains($normalise, 'starter') => 'starters',
            str_contains($normalise, 'capture'), str_contains($normalise, 'image'), str_contains($normalise, 'galerie') => 'captures',
            str_contains($normalise, 'trailer'), str_contains($normalise, 'bande-annonce'), str_contains($normalise, 'video') => 'trailer',
            str_contains($normalise, 'avis'), str_contains($normalise, 'conclusion') => 'avis',
            str_contains($normalise, 'credit'), str_contains($normalise, 'auteur'), str_contains($normalise, 'equipe') => 'credits',
            default => 'presentation',
        };
    }
}
