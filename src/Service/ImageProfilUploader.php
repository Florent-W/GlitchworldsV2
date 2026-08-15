<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageProfilUploader
{
    public function __construct(private readonly string $projectDir) {}

    public function enregistrer(UploadedFile $fichier, Utilisateur $utilisateur, string $type): string
    {
        if (!in_array($type, ['avatar', 'banniere'], true) || null === $utilisateur->getId()) {
            throw new \InvalidArgumentException('Type d’image de profil invalide.');
        }
        $extension = $fichier->guessExtension() ?: 'jpg';
        $nom = $type.'.'.$extension;
        $dossier = $this->projectDir.'/public/uploads/utilisateurs/'.$utilisateur->getId();
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier du profil.');
        }
        foreach (glob($dossier.'/'.$type.'.*') ?: [] as $ancien) {
            if (is_file($ancien)) { unlink($ancien); }
        }
        $fichier->move($dossier, $nom);

        return $nom;
    }
}
