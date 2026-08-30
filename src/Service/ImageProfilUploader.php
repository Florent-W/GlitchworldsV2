<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ImageProfilUploader
{
    public function __construct(
        private readonly string $projectDir,
        private readonly OptimiseurImage $optimiseur,
    ) {
    }

    public function enregistrer(UploadedFile $fichier, Utilisateur $utilisateur, string $type): string
    {
        if (!in_array($type, ['avatar', 'banniere'], true) || null === $utilisateur->getId()) {
            throw new \InvalidArgumentException('Type d’image de profil invalide.');
        }
        $dossier = $this->projectDir.'/public/uploads/utilisateurs/'.$utilisateur->getId();
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier du profil.');
        }
        foreach (glob($dossier.'/'.$type.'*') ?: [] as $ancien) {
            if (is_file($ancien)) { unlink($ancien); }
        }

        return $this->optimiseur->enregistrer($fichier, $dossier, $type);
    }

    public function supprimerMedias(Utilisateur $utilisateur): void
    {
        if (null === $utilisateur->getId()) {
            return;
        }

        $dossier = $this->projectDir.'/public/uploads/utilisateurs/'.$utilisateur->getId();
        if (!is_dir($dossier)) {
            return;
        }

        foreach (new \FilesystemIterator($dossier) as $fichier) {
            if ($fichier->isFile()) {
                unlink($fichier->getPathname());
            }
        }
        rmdir($dossier);
    }
}
