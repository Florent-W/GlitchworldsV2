<?php

namespace App\Tests\Service;

use App\Service\DecoupeurSectionsJeu;
use PHPUnit\Framework\TestCase;

final class DecoupeurSectionsJeuTest extends TestCase
{
    public function testIlNeDecoupeQueLesBalisesDeSectionExplicites(): void
    {
        $decoupeur = new DecoupeurSectionsJeu();

        self::assertSame([], $decoupeur->decouper('[titre=h4]Histoire[/titre]Ancienne fiche'));
        self::assertSame([
            ['type' => 'histoire', 'titre' => 'Histoire', 'contenu' => 'Une aventure.'],
            ['type' => 'avis', 'titre' => 'Mon avis', 'contenu' => '[b]Très bon jeu[/b]'],
        ], $decoupeur->decouper('[section type=histoire titre="Histoire"]Une aventure.[/section][section type=avis titre="Mon avis"][b]Très bon jeu[/b][/section]'));
    }
}
