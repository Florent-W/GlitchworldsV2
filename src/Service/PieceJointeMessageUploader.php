<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PieceJointeMessageUploader
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private readonly string $projectDir) {}

    public function enregistrer(UploadedFile $fichier, int $conversationId): string
    {
        $dossier = $this->projectDir.'/var/messages/'.$conversationId;
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            throw new \RuntimeException('Impossible de créer le dossier des pièces jointes.');
        }
        $extension = $fichier->guessExtension() ?: 'bin';
        $nom = bin2hex(random_bytes(16)).'.'.$extension;
        $fichier->move($dossier, $nom);

        return $nom;
    }

    public function chemin(int $conversationId, string $nom): string
    {
        return $this->projectDir.'/var/messages/'.$conversationId.'/'.basename($nom);
    }
}
