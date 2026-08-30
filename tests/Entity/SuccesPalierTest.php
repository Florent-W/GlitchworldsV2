<?php

namespace App\Tests\Entity;

use App\Entity\Succes;
use PHPUnit\Framework\TestCase;

final class SuccesPalierTest extends TestCase
{
    public function testLePalierSuitLesMemeSeuilsQueLesRecompenses(): void
    {
        self::assertSame('commun', $this->succes(15)->getPalier());
        self::assertSame('rare', $this->succes(75)->getPalier());
        self::assertSame('epique', $this->succes(150)->getPalier());
        self::assertSame('mythique', $this->succes(250)->getPalier());
        self::assertSame('legendaire', $this->succes(400)->getPalier());
        self::assertSame('Légendaire', $this->succes(400)->getPalierLabel());
        self::assertSame('collection', (new Succes())->setCode('premier_jeu')->getCategorie());
        self::assertSame('creation', (new Succes())->setCode('premiere_actualite')->getCategorie());
        self::assertSame('progression', (new Succes())->setCode('niveau_50')->getCategorie());
    }

    private function succes(int $points): Succes
    {
        return (new Succes())->setPoints($points);
    }
}
