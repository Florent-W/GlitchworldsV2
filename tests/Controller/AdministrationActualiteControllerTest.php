<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Repository\ActualiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdministrationActualiteControllerTest extends WebTestCase
{
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

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($actualite);
        $entityManager->remove($entityManager->find(Utilisateur::class, $adminId));
        $entityManager->flush();
    }
}
