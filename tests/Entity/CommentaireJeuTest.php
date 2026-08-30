<?php

namespace App\Tests\Entity;

use App\Entity\CommentaireJeu;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CommentaireJeuTest extends KernelTestCase
{
    public function testUnCommentaireValideNeProduitAucuneErreur(): void
    {
        $commentaire = (new CommentaireJeu())->setContenu('Un commentaire utile.');

        self::assertCount(0, $this->validator()->validate($commentaire));
    }

    public function testUnCommentaireTropCourtEstRefuse(): void
    {
        $commentaire = (new CommentaireJeu())->setContenu('a');

        $erreurs = $this->validator()->validate($commentaire);

        self::assertGreaterThanOrEqual(1, $erreurs->count());
        self::assertSame('contenu', $erreurs[0]->getPropertyPath());
    }

    public function testUnCommentaireTropLongEstRefuse(): void
    {
        $commentaire = (new CommentaireJeu())->setContenu(str_repeat('a', 1001));

        self::assertGreaterThanOrEqual(1, $this->validator()->validate($commentaire)->count());
    }

    public function testUnCommentaireEspaceEstRefuse(): void
    {
        $commentaire = (new CommentaireJeu())->setContenu('   ');

        $erreurs = $this->validator()->validate($commentaire);

        self::assertGreaterThanOrEqual(1, $erreurs->count());
        self::assertSame('contenu', $erreurs[0]->getPropertyPath());
    }

    private function validator(): ValidatorInterface
    {
        self::bootKernel();

        return self::getContainer()->get(ValidatorInterface::class);
    }
}
