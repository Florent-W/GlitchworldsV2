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
        self::assertSame(
            ['resultats' => [], 'totaux' => [], 'total' => 0],
            json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)
        );
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
        self::assertSame(1, $donnees['totaux']['Membre']);
        self::assertSame(1, $donnees['total']);

        $entityManager->remove($membre); $entityManager->flush();
    }

    public function testLaRecherchePeutFiltrerLesActualitesParCategorie(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $actualite = (new \App\Entity\Actualite())
            ->setTitre('Filtre catégorie '.$suffixe)
            ->setSlug('filtre-categorie-'.$suffixe)
            ->setDescription('Actualité tutoriel pour test de recherche.')
            ->setContenu('Contenu.')
            ->setCategorie(\App\Enum\CategorieActualite::Tutoriels)
            ->setStatut(\App\Enum\StatutActualite::Publiee);
        $autre = (new \App\Entity\Actualite())
            ->setTitre('Filtre catégorie news '.$suffixe)
            ->setSlug('filtre-categorie-news-'.$suffixe)
            ->setDescription('Actualité news pour test de recherche.')
            ->setContenu('Contenu.')
            ->setCategorie(\App\Enum\CategorieActualite::News)
            ->setStatut(\App\Enum\StatutActualite::Publiee);
        $entityManager->persist($actualite);
        $entityManager->persist($autre);
        $entityManager->flush();
        $idActualite = $actualite->getId();
        $idAutre = $autre->getId();

        $client->request('GET', '/recherche?recherche='.$suffixe.'&type=actualite&categorie_actualite=tutoriels');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Filtre catégorie '.$suffixe);
        self::assertSelectorTextNotContains('body', 'Filtre catégorie news '.$suffixe);

        $entityManager->remove($entityManager->find(\App\Entity\Actualite::class, $idActualite));
        $entityManager->remove($entityManager->find(\App\Entity\Actualite::class, $idAutre));
        $entityManager->flush();
    }
}
