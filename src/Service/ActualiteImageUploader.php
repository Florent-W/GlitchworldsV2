<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ActualiteImageUploader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/actualites')]
        private readonly string $dossierCible,
        private readonly OptimiseurImage $optimiseur,
    ) {
    }

    public function enregistrer(UploadedFile $image, int $actualiteId, string $type): string
    {
        if (!in_array($type, ['miniature', 'banniere'], true)) {
            throw new \InvalidArgumentException('Type d’image d’actualité invalide.');
        }

        $dossierActualite = $this->dossierCible.DIRECTORY_SEPARATOR.$actualiteId;
        if (!is_dir($dossierActualite) && !mkdir($dossierActualite, 0775, true) && !is_dir($dossierActualite)) {
            throw new \RuntimeException('Impossible de créer le dossier des images d’actualités.');
        }

        foreach (glob($dossierActualite.'/'.$type.'*') ?: [] as $ancien) {
            if (is_file($ancien)) {
                unlink($ancien);
            }
        }

        return $this->optimiseur->enregistrer($image, $dossierActualite, $type);
    }

    public function supprimerImages(int $actualiteId): void
    {
        $dossier = $this->dossierCible.DIRECTORY_SEPARATOR.$actualiteId;
        if (!is_dir($dossier)) { return; }
        foreach (new \FilesystemIterator($dossier) as $fichier) {
            if ($fichier->isFile()) { unlink($fichier->getPathname()); }
        }
        rmdir($dossier);
    }
}
