<?php

namespace App\Tests\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SuiviJeuControllerTest extends WebTestCase
{
    public function testUnMembrePeutSuivrePuisNePlusSuivreUnJeu(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Suivi'.$suffixe)->setEmail('suivi-'.$suffixe.'@test.local');
        $jeu = (new Jeu())->setNom('Jeu suivi '.$suffixe)->setSlug('jeu-suivi-'.$suffixe)->setDescription('Jeu utilisé pour tester le suivi.')->setStatut(StatutJeu::Approuve);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($membre);
        $entityManager->persist($jeu);
        $entityManager->flush();
        $membreId = $membre->getId();
        $jeuId = $jeu->getId();

        $client->loginUser($membre);
        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Suivre')->form());
        self::assertResponseRedirects(sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $membre = $entityManager->find(Utilisateur::class, $membreId);
        self::assertCount(1, $membre->getJeuxSuivis());

        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Suivre')->form());
        self::assertResponseRedirects(sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $membre = $entityManager->find(Utilisateur::class, $membreId);
        self::assertCount(0, $membre->getJeuxSuivis());
        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($membre);
        $entityManager->flush();
    }
}
