<?php

namespace App\Tests\Entity;

use App\Entity\Jeu;
use PHPUnit\Framework\TestCase;

final class JeuTest extends TestCase
{
    public function testLesTextesFacultatifsAcceptentUneValeurNulle(): void
    {
        $jeu = (new Jeu())
            ->setDescription(null)
            ->setContenu(null);

        self::assertSame('', $jeu->getDescription());
        self::assertSame('', $jeu->getContenu());
    }

    public function testIlAccepteLesTroisModesDePresentation(): void
    {
        $jeu = new Jeu();

        self::assertSame('sections', $jeu->setTypePresentation('sections')->getTypePresentation());
        self::assertSame('sections_blocs', $jeu->setTypePresentation('sections_blocs')->getTypePresentation());
        self::assertSame('conteneur', $jeu->setTypePresentation('inconnu')->getTypePresentation());
    }
}
