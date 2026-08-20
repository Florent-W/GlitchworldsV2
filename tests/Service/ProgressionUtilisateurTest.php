<?php

namespace App\Tests\Service;

use App\Entity\Utilisateur;
use App\Service\ProgressionUtilisateur;
use PHPUnit\Framework\TestCase;

final class ProgressionUtilisateurTest extends TestCase
{
    public function testLesNiveauxConserventLeSurplusExperience(): void
    {
        $utilisateur = (new Utilisateur())->setExperience(175);

        self::assertSame(3, $utilisateur->getNiveau());
        self::assertSame(80, $utilisateur->getExperienceNiveau());
        self::assertSame(85, $utilisateur->getExperienceNiveauSuivant());
        self::assertSame(94, $utilisateur->getProgressionNiveau());
    }

    public function testDeuxCentsPointsDeProgressionAtteignentLeNiveauQuatre(): void
    {
        $utilisateur = (new Utilisateur())->setExperience(200);

        self::assertSame(4, $utilisateur->getNiveau());
        self::assertSame(20, $utilisateur->getExperienceNiveau());
        self::assertSame(110, $utilisateur->getExperienceNiveauSuivant());
    }
}
