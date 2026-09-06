<?php

namespace App\Tests\Service;

use App\Service\ConvertisseurSectionsJeu;
use PHPUnit\Framework\TestCase;

final class ConvertisseurSectionsJeuTest extends TestCase
{
    public function testConvertitUneAncienneFicheEnSections(): void
    {
        $convertisseur = new ConvertisseurSectionsJeu();
        $resultat = $convertisseur->convertir(
            '[center]Introduction[/center]'.
            '[center][titre=h4][icone=histoire.png][/icone]Histoire :[/titre][/center]Une aventure.'.
            '[titre=h4]Fonctionnalités[/titre][titre=h5]Combat[/titre]Du contenu.'
        );

        self::assertNull($resultat['raison']);
        self::assertSame(3, $resultat['sections']);
        self::assertStringContainsString('[section type=presentation titre="Présentation générale"]', $resultat['contenu']);
        self::assertStringContainsString('[section type=histoire titre="Histoire"]', $resultat['contenu']);
        self::assertStringContainsString('[section type=fonctionnalites titre="Fonctionnalités"]', $resultat['contenu']);
        self::assertStringContainsString('[titre=h5]Combat[/titre]', $resultat['contenu']);
    }

    public function testNeTouchePasUneFicheDejaStructuree(): void
    {
        $convertisseur = new ConvertisseurSectionsJeu();
        $contenu = '[section type=histoire titre="Histoire"]Une aventure.[/section]';

        self::assertSame('deja_structure', $convertisseur->convertir($contenu)['raison']);
    }

    public function testRefuseUneFicheSansAssezDeSections(): void
    {
        $convertisseur = new ConvertisseurSectionsJeu();

        self::assertSame('moins_de_deux_sections', $convertisseur->convertir('[titre=h4]Histoire[/titre]Une aventure.')['raison']);
    }

    public function testEquilibreLesBalisesQuiTraversentLesSections(): void
    {
        $convertisseur = new ConvertisseurSectionsJeu();
        $contenu = '[center][titre=h4]Histoire[/titre]Une aventure.[titre=h4]Gameplay[/titre]Du contenu.[/center]';
        $resultat = $convertisseur->convertir($contenu);

        self::assertNull($resultat['raison']);
        self::assertSame(2, $resultat['sections']);
        self::assertStringContainsString('[center]Une aventure.[/center]', $resultat['contenu']);
        self::assertStringContainsString('[center]Du contenu.[/center]', $resultat['contenu']);
    }
}
