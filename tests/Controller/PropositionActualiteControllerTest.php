<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PropositionActualiteControllerTest extends WebTestCase
{
    public function testUnVisiteurEstRedirigeVersLaConnexion(): void
    {
        $client = self::createClient();
        $client->request('GET', '/actualite/proposer');

        self::assertResponseRedirects('/connexion');
    }

    public function testUnMembrePeutEnvoyerUneActualiteEnAttente(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $titre = 'Mon actualité Symfony '.$suffixe;
        $utilisateur = (new Utilisateur())->setPseudo('AuteurActuTest')->setEmail('auteur-actu-'.$suffixe.'@glitchworlds.local');
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $idUtilisateur = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/actualite/proposer');
        self::assertSelectorExists('[data-controller="bbcode"]');
        self::assertSelectorExists('button[data-bbcode-id-param="glitch"]');
        self::assertSelectorExists('body[data-turbo="false"]');

        $crawler = $client->request('GET', '/actualite/proposer');
        $form = $crawler->selectButton('Envoyer pour validation')->form([
            'actualite_proposition[titre]' => $titre,
            'actualite_proposition[description]' => 'Une actualité créée pendant le test.',
            'actualite_proposition[contenu]' => 'Contenu détaillé de l’actualité.',
            'actualite_proposition[categorie]' => CategorieActualite::News->value,
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/mon-compte');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $actualite = $entityManager->getRepository(Actualite::class)->findOneBy(['titre' => $titre]);
        self::assertInstanceOf(Actualite::class, $actualite);
        self::assertSame(StatutActualite::EnAttente, $actualite->getStatut());
        self::assertSame($idUtilisateur, $actualite->getAuteur()?->getId());
        self::assertSame('mon-actualite-symfony-'.$suffixe, $actualite->getSlug());
        $actualiteId = $actualite->getId();

        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('body', $titre);
        $crawler = $client->click($crawler->selectLink('Modifier la proposition')->link());
        $client->submit($crawler->selectButton('Enregistrer les modifications')->form([
            'actualite_proposition[titre]' => $titre.' Modifié',
        ]));
        self::assertResponseRedirects('/mon-compte');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $actualite = $entityManager->find(Actualite::class, $actualiteId);
        self::assertSame($titre.' Modifié', $actualite->getTitre());
        self::assertSame(StatutActualite::EnAttente, $actualite->getStatut());
    }

    public function testLaListeActualitesAfficheLeBoutonProposerPourUnMembreConnecte(): void
    {
        $client = self::createClient();
        $utilisateur = (new Utilisateur())->setPseudo('LecteurActu')->setEmail('lecteur-actu-'.bin2hex(random_bytes(4)).'@glitchworlds.local');
        self::getContainer()->get(EntityManagerInterface::class)->persist($utilisateur);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/actualites');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href="/actualite/proposer"]'));
    }
}
