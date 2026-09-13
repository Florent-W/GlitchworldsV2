<?php
namespace App\Tests\Controller;
use App\Entity\Avis;
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
        $jeuHorsBibliotheque = (new Jeu())->setNom('Jeu hors bibliothèque')->setSlug('jeu-hors-bibliotheque-'.$suffixe)->setDescription('Jeu ajouté directement à une liste.')->setStatut(StatutJeu::Approuve)->setMiniature('miniature.jpg');
        $avis = (new Avis())->setAuteur($membre)->setJeu($jeuHorsBibliotheque)->setNote(4.5)->setContenu('Très bon jeu.');
        $em->persist($membre); $em->persist($jeu); $em->persist($jeuHorsBibliotheque); $em->persist($avis); $em->flush();
        $membreId = $membre->getId(); $jeuId = $jeu->getId(); $jeuHorsBibliothequeId = $jeuHorsBibliotheque->getId();

        $client->loginUser($membre);
        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Mes jeux')->form());
        self::assertResponseRedirects('/mes-jeux?onglet=bibliotheque');

        $client->followRedirect(); self::assertResponseIsSuccessful(); self::assertSelectorTextContains('body', 'Jeu bibliothèque'); self::assertSelectorTextContains('body', 'Premier pas');
        $em = self::getContainer()->get(EntityManagerInterface::class); $em->clear(); $membre = $em->find(Utilisateur::class, $membreId);
        self::assertSame(45, $membre?->getPoints()); self::assertCount(1, $em->getRepository(JeuBibliotheque::class)->findBy(['utilisateur' => $membre])); self::assertCount(2, $em->getRepository(Notification::class)->findBy(['utilisateur' => $membre]));

        $crawler = $client->request('GET', '/mes-jeux?onglet=listes');
        self::assertSelectorExists('input[name="visibilite"][value="publique"]:checked');
        self::assertSelectorExists('input[name="visibilite"][value="privee"]');
        $client->submit($crawler->selectButton('Créer la liste')->form(['nom' => 'À découvrir', 'visibilite' => 'privee'])); self::assertResponseRedirects('/mes-jeux?onglet=listes#listes');
        $liste = $em->getRepository(ListeJeux::class)->findOneBy(['utilisateur' => $membre]);
        self::assertNotNull($liste);

        $crawler = $client->request('GET', '/mes-jeux');
        self::assertSelectorExists('[data-bs-target="#publiques"].active');
        self::assertSelectorExists('#publiques.show.active');
        $formulaire = $crawler->filter(sprintf('form[action="/mes-jeux/listes/%d/jeux"]', $liste->getId()))->form(['jeu_id' => $jeuHorsBibliothequeId]);
        $client->submit($formulaire);
        self::assertResponseRedirects('/mes-jeux?onglet=listes#listes');
        $crawler = $client->followRedirect();
        self::assertSelectorExists('[data-bs-target="#listes"].active');
        self::assertSelectorTextContains('#listes', 'Jeu hors bibliothèque');
        self::assertSelectorTextContains('#listes', 'Ma note : 4,5/5');

        $formulaire = $crawler->filter(sprintf('form[action="/mes-jeux/listes/%d/jeux"]', $liste->getId()))->form(['jeu_id' => $jeuId]);
        $client->submit($formulaire);
        $crawler = $client->followRedirect();
        $classement = $crawler->filter(sprintf('[data-controller="liste-classement"][data-liste-classement-url-value="/mes-jeux/listes/%d/ordre"]', $liste->getId()));
        self::assertCount(1, $classement);
        $client->jsonRequest('POST', sprintf('/mes-jeux/listes/%d/ordre', $liste->getId()), [
            '_token' => $classement->attr('data-liste-classement-token-value'),
            'ordre' => [$jeuId, $jeuHorsBibliothequeId],
        ]);
        self::assertResponseIsSuccessful();

        $em = self::getContainer()->get(EntityManagerInterface::class); $em->clear();
        $membre = $em->find(Utilisateur::class, $membreId);
        $liste = $em->getRepository(ListeJeux::class)->findOneBy(['utilisateur' => $membre]);
        self::assertCount(2, $liste?->getJeux() ?? []);
        self::assertSame($jeuId, $liste?->getJeux()[0]->getId());
        self::assertCount(1, $em->getRepository(JeuBibliotheque::class)->findBy(['utilisateur' => $membre]), 'Ajouter à une liste ne doit pas ajouter le jeu à la bibliothèque.');

        $client->request('GET', sprintf('/liste/a-decouvrir-%d', $liste->getId()));
        self::assertResponseStatusCodeSame(404, 'Une liste privée ne doit pas être consultable par son URL.');

        $crawler = $client->request('GET', '/mes-jeux?onglet=listes');
        $client->submit($crawler->selectButton('Rendre publique')->form());
        self::assertResponseRedirects('/mes-jeux?onglet=listes#listes');
        $em->clear();
        $liste = $em->getRepository(ListeJeux::class)->find($liste->getId());
        self::assertTrue($liste?->isPublique());
        self::assertSame('a-decouvrir', $liste?->getSlug());

        $crawler = $client->request('GET', '/mes-jeux?onglet=listes');
        $client->submit($crawler->selectButton('Créer la liste')->form(['nom' => 'Mes meilleurs ROM hacks', 'visibilite' => 'publique']));
        self::assertResponseRedirects('/mes-jeux?onglet=listes#listes');
        $em->clear();
        $listeCreeePublique = $em->getRepository(ListeJeux::class)->findOneBy(['nom' => 'Mes meilleurs ROM hacks', 'utilisateur' => $membreId]);
        self::assertTrue($listeCreeePublique?->isPublique());
        self::assertSame('mes-meilleurs-rom-hacks', $listeCreeePublique?->getSlug());

        $client->request('GET', '/recherche/autocompletion?type=liste&recherche=ROM');
        self::assertResponseIsSuccessful();
        $suggestions = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Mes meilleurs ROM hacks', $suggestions['resultats'][0]['titre'] ?? null);
        self::assertSame('Liste', $suggestions['resultats'][0]['type'] ?? null);

        $client->request('GET', '/mes-jeux?onglet=publiques&q=ROM');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#publiques', 'Mes meilleurs ROM hacks');

        $client->getCookieJar()->clear();
        $client->request('GET', sprintf('/liste/mauvais-slug-%d', $liste->getId()));
        self::assertResponseRedirects(sprintf('/liste/a-decouvrir-%d', $liste->getId()), 301);
        $client->request('GET', sprintf('/liste/a-decouvrir-%d', $liste->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'À découvrir');
        self::assertSelectorTextContains('body', 'Jeu bibliothèque');
        self::assertSelectorExists('script[type="application/ld+json"]');

        $client->request('GET', '/listes');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Listes publiques');
        self::assertSelectorTextContains('body', 'À découvrir');

        $client->request('GET', '/listes?q=ROM');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Mes meilleurs ROM hacks');
        $client->request('GET', '/listes?q=RechercheIntrouvable');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Aucun résultat');

        $client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString(sprintf('/liste/a-decouvrir-%d', $liste->getId()), (string) $client->getResponse()->getContent());

        $em->remove($em->find(Jeu::class, $jeuHorsBibliothequeId));
        $em->remove($em->find(Jeu::class, $jeuId)); $em->remove($em->find(Utilisateur::class, $membreId)); $em->flush();
    }
}
