<?php
namespace App\Service;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
final readonly class ImagePublicationUploader
{
    public function __construct(private string $projectDir, private SluggerInterface $slugger) {}
    public function enregistrer(UploadedFile $fichier, int $publicationId): string
    {
        $extension = $fichier->guessExtension() ?: 'bin'; $nom = 'image-'.bin2hex(random_bytes(6)).'.'.$extension;
        $dossier = $this->projectDir.'/public/uploads/publications/'.$publicationId; if (!is_dir($dossier)) { mkdir($dossier, 0775, true); }
        $fichier->move($dossier, $nom); return $nom;
    }
}
