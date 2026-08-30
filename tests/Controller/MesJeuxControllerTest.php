<?php
namespace App\Tests\Controller;
use App\Entity\Jeu;
use App\Entity\JeuBibliotheque;
use App\Entity\ListeJeux;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MesJeuxControllerTest extends WebTestCase
{
    public function testUnMembreOrganiseUnJeuEtDebloqueUnSucces(): void
    {
        $client = self::createClient(); $em = self::getContainer()->get(EntityManagerInterface::class); $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Bibliotheque'.$suffixe)->setEmail('bibliotheque-'.$suffixe.'@test.local');
        $jeu = (new Jeu())->setNom('Jeu bibliothèque')->setSlug('jeu-bibliotheque-'.$suffixe)->setDescription('Jeu de test pour Mes jeux.')->setStatut(StatutJeu::Approuve);
        $em->persist($membre); $em->persist($jeu); $em->flush(); $membreId = $membre->getId(); $jeuId = $jeu->getId();

        $client->loginUser($membre);
        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Ajouter à Mes jeux')->form());
        self::assertResponseRedirects('/mes-jeux');

        $client->followRedirect(); self::assertResponseIsSuccessful(); self::assertSelectorTextContains('body', 'Jeu bibliothèque'); self::assertSelectorTextContains('body', 'Premier pas');
        $em = self::getContainer()->get(EntityManagerInterface::class); $em->clear(); $membre = $em->find(Utilisateur::class, $membreId);
        self::assertSame(25, $membre?->getPoints()); self::assertCount(1, $em->getRepository(JeuBibliotheque::class)->findBy(['utilisateur' => $membre])); self::assertCount(1, $em->getRepository(Notification::class)->findBy(['utilisateur' => $membre]));

        $crawler = $client->request('GET', '/mes-jeux'); $client->submit($crawler->selectButton('Créer la liste')->form(['nom' => 'À découvrir'])); self::assertResponseRedirects('/mes-jeux#listes');
        self::assertCount(1, $em->getRepository(ListeJeux::class)->findBy(['utilisateur' => $membre]));

        $em = self::getContainer()->get(EntityManagerInterface::class); $em->clear();
        $em->remove($em->find(Jeu::class, $jeuId)); $em->remove($em->find(Utilisateur::class, $membreId)); $em->flush();
    }
}
