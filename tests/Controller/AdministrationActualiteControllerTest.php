<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Repository\ActualiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdministrationActualiteControllerTest extends WebTestCase
{
    public function testLeTableauDeBordEstReserveAuxAdministrateurs(): void
    {
        $client = self::createClient();
        $client->request('GET', '/administration');

        self::assertResponseRedirects('/connexion');
    }

    public function testUnAdministrateurPeutVoirLeTableauDeBordEtLesMembres(): void
    {
        $client = self::createClient();
        $administrateur = (new Utilisateur())
            ->setPseudo('AdminDashboard')
            ->setEmail('admin-dashboard-'.bin2hex(random_bytes(5)).'@glitchworlds.local')
            ->setRoles(['ROLE_ADMIN']);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($administrateur);
        $entityManager->flush();
        $adminId = $administrateur->getId();

        $client->loginUser($administrateur);
        $client->request('GET', '/administration');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Tableau de bord');
        self::assertSelectorCount(4, '.gw-admin-stat');
        self::assertSelectorExists('.gw-admin-chart[aria-label*="sept derniers jours"]');
        self::assertSelectorCount(7, '.gw-admin-chart__day');
        self::assertSelectorCount(3, '.gw-admin-summary');
        self::assertSelectorExists('a[href="/administration/membres"]');
        self::assertSelectorExists('a[href="/moderation/commentaires"]');

        $client->request('GET', '/administration/membres?recherche=AdminDashboard');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('tbody', 'AdminDashboard');
        self::assertSelectorTextContains('tbody', 'Administrateur');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Utilisateur::class, $adminId));
        $entityManager->flush();
    }

    public function testUnMembreNePeutPasAdministrerLesActualites(): void
    {
        $client = self::createClient();
        $utilisateur = (new Utilisateur())->setPseudo('MembreActu')->setEmail('membre-actu-'.bin2hex(random_bytes(5)).'@glitchworlds.local');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $id = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $client->request('GET', '/administration/actualites');
        self::assertResponseStatusCodeSame(403);

        $entityManager->remove($entityManager->find(Utilisateur::class, $id));
        $entityManager->flush();
    }

    public function testUnAdministrateurPeutCreerUneActualite(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(5));
        $administrateur = (new Utilisateur())->setPseudo('AdminActu')->setEmail('admin-actu-'.$suffixe.'@glitchworlds.local')->setRoles(['ROLE_ADMIN']);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($administrateur);
        $entityManager->flush();
        $adminId = $administrateur->getId();

        $client->loginUser($administrateur);
        $crawler = $client->request('GET', '/administration/actualites/creer');
        self::assertSelectorExists('[data-controller="bbcode"]');
        self::assertSelectorExists('button[data-bbcode-id-param="glitch"]');
        self::assertSelectorExists('body[data-turbo="false"]');
        self::assertStringContainsString('onbeforeunload', (string) $client->getResponse()->getContent());
        self::assertSelectorExists('[data-action="bbcode#tableau"]');
        self::assertSelectorExists('select[data-bbcode-modele-ouvrant="[titre={valeur}]"]');
        $client->submit($crawler->selectButton('Créer l’actualité')->form([
            'actualite[titre]' => 'Nouvelle actualité '.$suffixe,
            'actualite[description]' => 'Une description suffisamment précise pour le test.',
            'actualite[contenu]' => 'Le contenu complet de cette nouvelle actualité.',
            'actualite[categorie]' => 'news',
            'actualite[statut]' => 'publiee',
        ]));
        self::assertResponseRedirects('/administration/actualites');

        $actualiteRepository = self::getContainer()->get(ActualiteRepository::class);
        $actualite = $actualiteRepository->findOneBy(['titre' => 'Nouvelle actualité '.$suffixe]);
        self::assertInstanceOf(Actualite::class, $actualite);
        self::assertSame($administrateur->getId(), $actualite->getAuteur()?->getId());
        $actualiteId = $actualite->getId();

        $client->request('GET', sprintf('/actualite/%s-%d', $actualite->getSlug(), $actualiteId));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('a[href="/administration/actualites/%d/modifier"]', $actualiteId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Actualite::class, $actualiteId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $adminId));
        $entityManager->flush();
    }
}
