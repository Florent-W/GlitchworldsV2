<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RechercheControllerTest extends WebTestCase
{
    public function testAutocompletionAttendDeuxCaracteres(): void
    {
        $client = self::createClient();
        $client->request('GET', '/recherche/autocompletion?recherche=a');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame(['resultats' => []], json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testAutocompletionTrouveUnMembreParSonPseudo(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Autocompletion'.$suffixe)->setEmail('autocomplete-'.$suffixe.'@test.local');
        $entityManager->persist($membre); $entityManager->flush();
        $id = $membre->getId();

        $client->request('GET', '/recherche/autocompletion?recherche=Autocompletion'.$suffixe);
        self::assertResponseIsSuccessful();
        $donnees = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Membre', $donnees['resultats'][0]['type']);
        self::assertSame('Autocompletion'.$suffixe, $donnees['resultats'][0]['titre']);
        self::assertSame('/membre/'.$id, $donnees['resultats'][0]['url']);

        $entityManager->remove($membre); $entityManager->flush();
    }
}
