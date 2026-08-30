<?php

namespace App\Tests\Controller;

use App\Entity\AchatBoutique;
use App\Entity\ArticleBoutique;
use App\Entity\Utilisateur;
use App\Service\Boutique;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BoutiqueControllerTest extends WebTestCase
{
    public function testLaBoutiqueEstVisibleSansConnexion(): void
    {
        $client = self::createClient();
        $client->request('GET', '/boutique');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Récompenses');
        self::assertSelectorTextContains('body', 'Étoile Glitchworlds');
    }

    public function testUnMembrePeutAcheterEtEquiperUnTitreSansDoubleDebit(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $article = $entityManager->getRepository(ArticleBoutique::class)->findOneBy(['slug' => 'explorateur-du-glitch']);
        self::assertInstanceOf(ArticleBoutique::class, $article);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Acheteur'.$suffixe)->setEmail('acheteur-'.$suffixe.'@test.local')->setPoints(500);
        $entityManager->persist($membre); $entityManager->flush();
        $membreId = $membre->getId(); $articleId = $article->getId();

        self::getContainer()->get(Boutique::class)->acheter($membre, $article);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class); $entityManager->clear();
        $membre = $entityManager->find(Utilisateur::class, $membreId);
        self::assertSame(400, $membre?->getPoints());
        self::assertCount(1, $entityManager->getRepository(AchatBoutique::class)->findBy(['utilisateur' => $membre]));

        try {
            self::getContainer()->get(Boutique::class)->acheter($membre, $article);
            self::fail('Un article déjà possédé ne doit pas être débité une seconde fois.');
        } catch (\DomainException) {
            self::assertSame(400, $membre?->getPoints());
        }

        self::getContainer()->get(ManagerRegistry::class)->resetManager();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $membre = $entityManager->find(Utilisateur::class, $membreId);
        $article = $entityManager->find(ArticleBoutique::class, $articleId);
        $membre?->setTitreEquipe($article);
        $entityManager->flush();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class); $entityManager->clear();
        $membre = $entityManager->find(Utilisateur::class, $membreId);
        self::assertSame($articleId, $membre?->getTitreEquipe()?->getId());
        self::assertSame(400, $membre?->getPoints());

        $membre?->setTitreEquipe(null); $entityManager->flush();
        foreach ($entityManager->getRepository(AchatBoutique::class)->findBy(['utilisateur' => $membre]) as $achat) { $entityManager->remove($achat); }
        $entityManager->remove($membre); $entityManager->flush();
    }
}
