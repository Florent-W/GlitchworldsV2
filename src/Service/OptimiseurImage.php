<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class OptimiseurImage
{
    private const LARGEURS = [480, 960, 1440];

    public function enregistrer(UploadedFile $fichier, string $dossier, string $base): string
    {
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier de l’image.');
        }

        $contenu = file_get_contents($fichier->getPathname());
        $source = false !== $contenu ? imagecreatefromstring($contenu) : false;
        if (false === $source) {
            throw new \RuntimeException('Le fichier envoyé n’est pas une image exploitable.');
        }

        try {
            $source = $this->orienter($source, $fichier);
            $nom = $base.'.opt.webp';
            $master = $dossier.DIRECTORY_SEPARATOR.$nom;
            $this->exporter($source, $master, 1920, 'webp');
            imagedestroy($source);
            $source = imagecreatefromwebp($master);
            if (false === $source) {
                throw new \RuntimeException('Impossible de relire l’image optimisée.');
            }

            foreach (self::LARGEURS as $largeur) {
                $this->exporter($source, sprintf('%s%s%s.opt-%d.webp', $dossier, DIRECTORY_SEPARATOR, $base, $largeur), $largeur, 'webp');
                if (function_exists('imageavif')) {
                    $this->exporter($source, sprintf('%s%s%s.opt-%d.avif', $dossier, DIRECTORY_SEPARATOR, $base, $largeur), $largeur, 'avif');
                }
            }

            return $nom;
        } finally {
            if ($source instanceof \GdImage) {
                imagedestroy($source);
            }
        }
    }

    private function exporter(\GdImage $source, string $destination, int $largeurMaximale, string $format): void
    {
        $largeurSource = imagesx($source);
        $hauteurSource = imagesy($source);
        $largeur = min($largeurMaximale, $largeurSource);
        $hauteur = max(1, (int) round($hauteurSource * ($largeur / $largeurSource)));
        $image = imagecreatetruecolor($largeur, $hauteur);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $largeur, $hauteur, $largeurSource, $hauteurSource);

        try {
            $succes = 'avif' === $format
                ? imageavif($image, $destination, 55)
                : imagewebp($image, $destination, 82);
            if (!$succes) {
                throw new \RuntimeException('Impossible de générer la variante '.$format.'.');
            }
        } finally {
            imagedestroy($image);
        }
    }

    private function orienter(\GdImage $source, UploadedFile $fichier): \GdImage
    {
        if (!function_exists('exif_read_data') || 'image/jpeg' !== $fichier->getMimeType()) {
            return $source;
        }

        $exif = @exif_read_data($fichier->getPathname());
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if (0 === $angle) {
            return $source;
        }

        $imageOrientee = imagerotate($source, $angle, 0);
        if (false === $imageOrientee) {
            return $source;
        }
        imagedestroy($source);

        return $imageOrientee;
    }
}
