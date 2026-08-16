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

        self::assertSame(2, $utilisateur->getNiveau());
        self::assertSame(75, $utilisateur->getExperienceNiveau());
        self::assertSame(150, $utilisateur->getExperienceNiveauSuivant());
        self::assertSame(50, $utilisateur->getProgressionNiveau());
    }

    public function testUnePublicationRapporteExperienceEtPoints(): void
    {
        $utilisateur = (new Utilisateur())->setExperience(95)->setPoints(40);

        (new ProgressionUtilisateur())->recompensePublication($utilisateur);

        self::assertSame(115, $utilisateur->getExperience());
        self::assertSame(50, $utilisateur->getPoints());
        self::assertSame(2, $utilisateur->getNiveau());
        self::assertSame(15, $utilisateur->getExperienceNiveau());
    }
}
