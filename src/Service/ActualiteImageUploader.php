<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ActualiteImageUploader
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/actualites')]
        private readonly string $dossierCible,
    ) {
    }

    public function enregistrer(UploadedFile $image, int $actualiteId): string
    {
        $nomDossier = (string) $actualiteId;
        $dossierActualite = $this->dossierCible.DIRECTORY_SEPARATOR.$nomDossier;
        if (!is_dir($dossierActualite) && !mkdir($dossierActualite, 0775, true) && !is_dir($dossierActualite)) {
            throw new \RuntimeException('Impossible de créer le dossier des images d’actualités.');
        }

        $extension = $image->guessExtension() ?: 'bin';
        $nom = 'miniature.'.$extension;
        $image->move($dossierActualite, $nom);

        return $nom;
    }
}
