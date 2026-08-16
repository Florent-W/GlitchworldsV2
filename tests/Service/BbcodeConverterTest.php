<?php

namespace App\Tests\Service;

use App\Service\BbcodeConverter;
use PHPUnit\Framework\TestCase;

final class BbcodeConverterTest extends TestCase
{
    public function testConvertitLeBbcodeEtEchappeLeHtml(): void
    {
        $html = (new BbcodeConverter())->toHtml('[b]Important[/b] <script>alert(1)</script>');

        self::assertStringContainsString('<strong>Important</strong>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testRefuseLesProtocolesDangereuxDansLesLiens(): void
    {
        $convertisseur = new BbcodeConverter();

        self::assertStringContainsString('href="#"', $convertisseur->toHtml('[lien]javascript:alert(1)[/lien][texteLien]Cliquer[/texteLien]'));
        self::assertStringContainsString('href="https://glitchworlds.local/article"', $convertisseur->toHtml('[lien]https://glitchworlds.local/article[/lien][texteLien]Article[/texteLien]'));
    }

    public function testConvertitLesOptionsDeLEditeurHistorique(): void
    {
        $html = (new BbcodeConverter())->toHtml('[titre=h2]Titre[/titre][couleur=red]Rouge[/couleur][gallery][image]https://example.com/image.png[/image][/gallery][section]Bloc[/section][animation=fade-left]Animé[/animation][icone=histoir.png][/icone]');

        self::assertStringContainsString('<h2>Titre</h2>', $html);
        self::assertStringContainsString('style="color: red"', $html);
        self::assertStringContainsString('class="gw-bbcode-gallery"', $html);
        self::assertStringContainsString('loading="lazy"', $html);
        self::assertStringContainsString('decoding="async"', $html);
        self::assertStringContainsString('class="gw-bbcode-section"', $html);
        self::assertStringContainsString('gw-bbcode-animation--fade-left', $html);
        self::assertStringContainsString('/icones/histoir.png', $html);
    }
}
