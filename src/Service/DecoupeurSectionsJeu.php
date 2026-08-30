<?php

namespace App\Service;

final class DecoupeurSectionsJeu
{
    /** @return list<array{type: string, titre: string, contenu: string}> */
    public function decouper(string $contenu): array
    {
        preg_match_all('#\[section\s+type=([a-z0-9_-]+)\s+titre=(?:"([^"]*)"|\'([^\']*)\')\](.*?)\[/section\]#is', $contenu, $correspondances, PREG_SET_ORDER);
        $sections = [];
        foreach ($correspondances as $correspondance) {
            $type = strtolower($correspondance[1]);
            $sections[] = [
                'type' => preg_match('/^[a-z0-9_-]+$/', $type) === 1 ? $type : 'generale',
                'titre' => trim($correspondance[2] !== '' ? $correspondance[2] : $correspondance[3]),
                'contenu' => trim($correspondance[4]),
            ];
        }

        return $sections;
    }
}
