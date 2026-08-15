<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfilControllerTest extends WebTestCase
{
    public function testLeProfilEstPublicSansAfficherEmail(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Profil'.$suffixe)->setEmail('prive-'.$suffixe.'@test.local')->setBiographie('Une présentation publique.');
        $entityManager->persist($membre); $entityManager->flush();

        $client->request('GET', '/membre/'.$membre->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Profil'.$suffixe);
        self::assertSelectorTextContains('body', 'Une présentation publique.');
        self::assertSelectorTextNotContains('body', 'prive-'.$suffixe.'@test.local');

        $entityManager->remove($membre); $entityManager->flush();
    }

    public function testUnMembrePeutSuivrePuisNePlusSuivreUnAutreMembre(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $abonne = (new Utilisateur())->setPseudo('Abonne'.$suffixe)->setEmail('abonne-'.$suffixe.'@test.local');
        $suivi = (new Utilisateur())->setPseudo('Suivi'.$suffixe)->setEmail('suivi-'.$suffixe.'@test.local');
        $entityManager->persist($abonne); $entityManager->persist($suivi); $entityManager->flush();
        $abonneId = $abonne->getId(); $suiviId = $suivi->getId();

        $client->loginUser($abonne);
        $crawler = $client->request('GET', '/membre/'.$suiviId);
        $client->submit($crawler->selectButton('Suivre')->form());
        self::assertResponseRedirects('/membre/'.$suiviId);
        $entityManager->clear();
        self::assertTrue($entityManager->find(Utilisateur::class, $abonneId)->suit($entityManager->find(Utilisateur::class, $suiviId)));

        $crawler = $client->request('GET', '/membre/'.$suiviId);
        $client->submit($crawler->selectButton('Ne plus suivre')->form());
        $entityManager->clear();
        self::assertFalse($entityManager->find(Utilisateur::class, $abonneId)->suit($entityManager->find(Utilisateur::class, $suiviId)));

        $entityManager->remove($entityManager->find(Utilisateur::class, $abonneId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $suiviId));
        $entityManager->flush();
    }
}
