<?php

namespace App\Service;

use App\Entity\Utilisateur;

final class ProgressionUtilisateur
{
    public const COMMENTAIRE_XP = 10;
    public const COMMENTAIRE_POINTS = 5;
    public const PUBLICATION_XP = 20;
    public const PUBLICATION_POINTS = 10;
    public const JEU_APPROUVE_XP = 100;
    public const JEU_APPROUVE_POINTS = 50;

    public function recompenseCommentaire(Utilisateur $utilisateur): void
    {
        $this->ajouter($utilisateur, self::COMMENTAIRE_XP, self::COMMENTAIRE_POINTS);
    }

    public function recompensePublication(Utilisateur $utilisateur): void
    {
        $this->ajouter($utilisateur, self::PUBLICATION_XP, self::PUBLICATION_POINTS);
    }

    public function recompenseJeuApprouve(Utilisateur $utilisateur): void
    {
        $this->ajouter($utilisateur, self::JEU_APPROUVE_XP, self::JEU_APPROUVE_POINTS);
    }

    private function ajouter(Utilisateur $utilisateur, int $experience, int $points): void
    {
        $utilisateur
            ->setExperience($utilisateur->getExperience() + $experience)
            ->setPoints($utilisateur->getPoints() + $points);
    }
}
