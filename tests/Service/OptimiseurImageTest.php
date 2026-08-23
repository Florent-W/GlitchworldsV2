<?php

namespace App\Tests\Service;

use App\Service\OptimiseurImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class OptimiseurImageTest extends TestCase
{
    public function testGenereUnMasterWebpEtDesVariantesResponsives(): void
    {
        $dossier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gw-images-'.bin2hex(random_bytes(5));
        mkdir($dossier, 0775, true);
        $source = $dossier.DIRECTORY_SEPARATOR.'source.png';
        $image = imagecreatetruecolor(1600, 900);
        imagefilledrectangle($image, 0, 0, 1599, 899, imagecolorallocate($image, 80, 30, 180));
        imagepng($image, $source);
        imagedestroy($image);

        try {
            $nom = (new OptimiseurImage())->enregistrer(
                new UploadedFile($source, 'source.png', 'image/png', null, true),
                $dossier,
                'miniature',
            );

            self::assertSame('miniature.opt.webp', $nom);
            self::assertFileExists($dossier.DIRECTORY_SEPARATOR.$nom);
            self::assertFileExists($dossier.DIRECTORY_SEPARATOR.'miniature.opt-480.webp');
            self::assertFileExists($dossier.DIRECTORY_SEPARATOR.'miniature.opt-960.webp');
            self::assertFileExists($dossier.DIRECTORY_SEPARATOR.'miniature.opt-1440.webp');
            $dimensions = getimagesize($dossier.DIRECTORY_SEPARATOR.'miniature.opt-480.webp');
            self::assertSame(480, $dimensions[0]);
            self::assertSame(270, $dimensions[1]);
            if (function_exists('imageavif')) {
                self::assertFileExists($dossier.DIRECTORY_SEPARATOR.'miniature.opt-480.avif');
            }
        } finally {
            foreach (glob($dossier.DIRECTORY_SEPARATOR.'*') ?: [] as $fichier) {
                unlink($fichier);
            }
            rmdir($dossier);
        }
    }
}
