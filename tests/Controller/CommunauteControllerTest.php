<?php

namespace App\Tests\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CommunauteControllerTest extends WebTestCase
{
    public function testLaPageCommunauteEstPublique(): void
    {
        $client = self::createClient();
        $client->request('GET', '/communaute');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Communauté');
        self::assertSelectorExists('a[href="/communaute"][aria-current="page"]');
    }

    public function testUnMembrePeutPublierDansLeFil(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $contenu = 'Publication Symfony '.$suffixe;
        $utilisateur = (new Utilisateur())->setPseudo('Membre'.$suffixe)->setEmail('membre-'.$suffixe.'@glitchworlds.local');
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/communaute');
        $client->submit($crawler->selectButton('Publier')->form([
            'publication[contenu]' => $contenu,
        ]));

        self::assertResponseRedirects('/communaute#fil');
        $publication = $entityManager->getRepository(Publication::class)->findOneBy(['contenu' => $contenu]);
        self::assertInstanceOf(Publication::class, $publication);
        self::assertSame($utilisateurId, $publication->getAuteur()?->getId());
        $publicationId = $publication->getId();

        $crawler = $client->followRedirect();
        $formulaireAimer = $crawler->filter(sprintf('form[action="/communaute/publication/%d/aimer"]', $publicationId))->form();
        $client->submit($formulaireAimer);
        self::assertResponseRedirects('/communaute#publication-'.$publicationId);
        $entityManager->clear();
        self::assertCount(1, $entityManager->find(Publication::class, $publicationId)?->getAimePar());

        $crawler = $client->followRedirect();
        $crawler = $client->click($crawler->selectLink('Modifier')->link());
        $contenuModifie = $contenu.' modifiée';
        $client->submit($crawler->selectButton('Enregistrer')->form([
            'publication[contenu]' => $contenuModifie,
        ]));
        self::assertResponseRedirects('/communaute#fil');

        $entityManager->clear();
        self::assertSame($contenuModifie, $entityManager->find(Publication::class, $publicationId)?->getContenu());
        $crawler = $client->followRedirect();
        $client->submit($crawler->selectButton('Supprimer')->form());
        self::assertResponseRedirects('/communaute#fil');

        $entityManager->clear();
        self::assertNull($entityManager->find(Publication::class, $publicationId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }
}
