<?php

namespace App\Tests\Entity;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

final class UtilisateurSessionTest extends TestCase
{
    public function testInvaliderAutresSessionsRendUnAncienJetonDifferent(): void
    {
        $utilisateur = (new Utilisateur())
            ->setPseudo('TestSession')
            ->setEmail('session@glitchworlds.local')
            ->setPassword('mot-de-passe-hache');
        $ancienJeton = clone $utilisateur;

        self::assertTrue($utilisateur->isEqualTo($ancienJeton));

        $utilisateur->invaliderAutresSessions();

        self::assertSame(2, $utilisateur->getVersionSession());
        self::assertFalse($utilisateur->isEqualTo($ancienJeton));
    }
}
