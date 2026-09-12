<?php

namespace App\Tests\Twig;

use App\Twig\TexteExtension;
use PHPUnit\Framework\TestCase;

final class TexteExtensionTest extends TestCase
{
    public function testExtraitCoupeEntreDeuxMotsEtAjouteUneEllipse(): void
    {
        self::assertSame('Une description…', (new TexteExtension())->extrait('Une description assez longue', 19));
    }

    public function testExtraitConserveLeTexteCompletQuandIlEstAssezCourt(): void
    {
        self::assertSame('Texte complet.', (new TexteExtension())->extrait('Texte complet.', 30));
    }
}
