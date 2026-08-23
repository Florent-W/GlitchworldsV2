<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class JeuGalerieUploader
{
    public function __construct(
        private string $projectDir,
        private SluggerInterface $slugger,
        private OptimiseurImage $optimiseur,
    ) {
    }

    public function enregistrer(UploadedFile $image, int $jeuId): string
    {
        $dossier = $this->dossier($jeuId);
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier de la galerie.');
        }

        $base = $this->slugger->slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))->lower();
        $baseFichier = sprintf('galerie-%s-%s', $base ?: 'image', bin2hex(random_bytes(4)));
        if ('image/gif' === $image->getMimeType()) {
            $nom = $baseFichier.'.gif';
            $image->move($dossier, $nom);

            return $nom;
        }

        return $this->optimiseur->enregistrer($image, $dossier, $baseFichier);
    }

    public function enregistrerHabillage(UploadedFile $image, int $jeuId, string $type): string
    {
        if (!in_array($type, ['miniature', 'banniere'], true)) {
            throw new \InvalidArgumentException('Type d’habillage de jeu invalide.');
        }

        $dossier = $this->dossier($jeuId);
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier du jeu.');
        }

        foreach (glob($dossier.'/'.$type.'*') ?: [] as $ancien) {
            if (is_file($ancien)) {
                unlink($ancien);
            }
        }

        return $this->optimiseur->enregistrer($image, $dossier, $type);
    }

    public function supprimer(string $nom, int $jeuId): void
    {
        if (!preg_match('/^(galerie-[a-z0-9-]+)(?:\.opt)?\.(?:jpe?g|png|webp|gif)$/i', $nom, $correspondances)) {
            return;
        }

        foreach (glob($this->dossier($jeuId).DIRECTORY_SEPARATOR.$correspondances[1].'*') ?: [] as $fichier) {
            if (is_file($fichier)) {
                unlink($fichier);
            }
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
