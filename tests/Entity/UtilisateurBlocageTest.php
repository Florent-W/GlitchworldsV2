<?php

namespace App\Tests\Entity;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

final class UtilisateurBlocageTest extends TestCase
{
    public function testLeBlocageEstPrisEnCompteDansLesDeuxSens(): void
    {
        $alice = (new Utilisateur())->setPseudo('Alice');
        $bob = (new Utilisateur())->setPseudo('Bob');

        $alice->bloquer($bob);

        self::assertTrue($alice->aBloque($bob));
        self::assertTrue($alice->interactionBloqueeAvec($bob));
        self::assertTrue($bob->interactionBloqueeAvec($alice));

        $alice->debloquer($bob);

        self::assertFalse($alice->interactionBloqueeAvec($bob));
        self::assertFalse($bob->interactionBloqueeAvec($alice));
    }
}
