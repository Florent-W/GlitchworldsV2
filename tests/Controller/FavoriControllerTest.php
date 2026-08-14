<?php

namespace App\Tests\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FavoriControllerTest extends WebTestCase
{
    public function testUnVisiteurEstRedirigeVersLaConnexion(): void
    {
        $client = self::createClient();
        $client->request('GET', '/favoris');

        self::assertResponseRedirects('/connexion');
    }

    public function testUnMembrePeutAjouterPuisRetirerUnJeuFavori(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $utilisateur = (new Utilisateur())
            ->setPseudo('FavoriTest')
            ->setEmail(sprintf('favori-%s@glitchworlds.local', $suffixe));
        $jeu = (new Jeu())
            ->setNom('Jeu favori de test')
            ->setSlug('jeu-favori-'.$suffixe)
            ->setDescription('Jeu utilisé pour tester les favoris.')
            ->setStatut(StatutJeu::Approuve);
        $entityManager->persist($utilisateur);
        $entityManager->persist($jeu);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();
        $jeuId = $jeu->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Ajouter aux favoris')->form());
        self::assertResponseRedirects(sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        self::assertCount(1, $utilisateur->getJeuxFavoris());

        $client->request('GET', '/favoris');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Jeu favori de test');

        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Retirer des favoris')->form());
        self::assertResponseRedirects(sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        self::assertCount(0, $utilisateur->getJeuxFavoris());

        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($utilisateur);
        $entityManager->flush();
    }
}
