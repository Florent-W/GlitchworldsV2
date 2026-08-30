<?php
namespace App\Service;
use Symfony\Component\HttpFoundation\File\UploadedFile;
final readonly class ImagePublicationUploader
{
    public function __construct(
        private string $projectDir,
        private OptimiseurImage $optimiseur,
    ) {
    }

    public function enregistrer(UploadedFile $fichier, int $publicationId): string
    {
        $base = 'image-'.bin2hex(random_bytes(6));
        $dossier = $this->projectDir.'/public/uploads/publications/'.$publicationId;
        if ('image/gif' === $fichier->getMimeType()) {
            if (!is_dir($dossier)) {
                mkdir($dossier, 0775, true);
            }
            $nom = $base.'.gif';
            $fichier->move($dossier, $nom);

            return $nom;
        }

        return $this->optimiseur->enregistrer($fichier, $dossier, $base);
    }
}
