<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class JeuGalerieUploader
{
    public function __construct(
        private string $projectDir,
        private SluggerInterface $slugger,
    ) {
    }

    public function enregistrer(UploadedFile $image, int $jeuId): string
    {
        $dossier = $this->dossier($jeuId);
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier de la galerie.');
        }

        $base = $this->slugger->slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))->lower();
        $extension = $image->guessExtension() ?: 'bin';
        $nom = sprintf('galerie-%s-%s.%s', $base ?: 'image', bin2hex(random_bytes(4)), $extension);
        $image->move($dossier, $nom);

        return $nom;
    }

    public function supprimer(string $nom, int $jeuId): void
    {
        if (!preg_match('/^galerie-[a-z0-9-]+\.(?:jpe?g|png|webp|gif)$/i', $nom)) {
            return;
        }

        $fichier = $this->dossier($jeuId).DIRECTORY_SEPARATOR.$nom;
        if (is_file($fichier)) {
            unlink($fichier);
        }
    }

    public function supprimerMedias(int $jeuId): void
    {
        $dossier = $this->dossier($jeuId);
        if (!is_dir($dossier)) { return; }
        foreach (new \FilesystemIterator($dossier) as $fichier) {
            if ($fichier->isFile()) { unlink($fichier->getPathname()); }
        }
        rmdir($dossier);
    }

    private function dossier(int $jeuId): string
    {
        return $this->projectDir.'/public/uploads/jeux/'.$jeuId;
    }
}
