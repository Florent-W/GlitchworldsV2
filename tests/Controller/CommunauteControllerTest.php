<?php

namespace App\Tests\Controller;

use App\Entity\Avis;
use App\Entity\Jeu;
use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Entity\ReponsePublication;
use App\Entity\VotePublication;
use App\Enum\StatutJeu;
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

    public function testLOngletAbonnementsNeMontreQueLesMembresSuivis(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));

        $lecteur = (new Utilisateur())->setPseudo('Lecteur'.$suffixe)->setEmail('lecteur-'.$suffixe.'@glitchworlds.local');
        $suivi = (new Utilisateur())->setPseudo('Suivi'.$suffixe)->setEmail('suivi-'.$suffixe.'@glitchworlds.local');
        $inconnu = (new Utilisateur())->setPseudo('Inconnu'.$suffixe)->setEmail('inconnu-'.$suffixe.'@glitchworlds.local');
        $lecteur->suivre($suivi);
        $publicationSuivie = (new Publication())->setAuteur($suivi)->setContenu('Publication suivie '.$suffixe);
        $publicationIgnoree = (new Publication())->setAuteur($inconnu)->setContenu('Publication ignorée '.$suffixe);
        $jeu = (new Jeu())
            ->setNom('Jeu noté '.$suffixe)
            ->setSlug('jeu-note-fil-'.$suffixe)
            ->setDescription('Jeu utilisé pour vérifier les notes dans le fil.')
            ->setStatut(StatutJeu::Approuve);
        $note = (new Avis())->setAuteur($suivi)->setJeu($jeu)->setNote(4);
        foreach ([$lecteur, $suivi, $inconnu, $publicationSuivie, $publicationIgnoree, $jeu, $note] as $entite) {
            $entityManager->persist($entite);
        }
        $entityManager->flush();

        $client->loginUser($lecteur);
        $client->request('GET', '/communaute?section=abonnements');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Publication suivie '.$suffixe);
        self::assertSelectorTextNotContains('body', 'Publication ignorée '.$suffixe);
        self::assertSelectorTextContains('body', 'Jeu noté '.$suffixe);
        self::assertSelectorExists('.gw-community__stars[aria-label="4 sur 5"]');

        $entityManager->remove($note);
        $entityManager->remove($jeu);
        foreach ([$publicationSuivie, $publicationIgnoree] as $publication) {
            $entityManager->remove($publication);
        }
        $entityManager->flush();
        $lecteur->nePlusSuivre($suivi);
        $entityManager->flush();
        foreach ([$lecteur, $suivi, $inconnu] as $membre) {
            $entityManager->remove($membre);
        }
        $entityManager->flush();
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
            'publication[lien]' => 'https://example.com/projet',
            'publication[questionSondage]' => 'Quel choix préférez-vous ?',
            'publication[optionsSondageTexte]' => "Option A\nOption B",
        ]));

        self::assertResponseRedirects('/communaute#fil');
        $publication = $entityManager->getRepository(Publication::class)->findOneBy(['contenu' => $contenu]);
        self::assertInstanceOf(Publication::class, $publication);
        self::assertSame($utilisateurId, $publication->getAuteur()?->getId());
        $publicationId = $publication->getId();
        self::assertTrue($publication->isSondage());
        self::assertSame('https://example.com/projet', $publication->getLien());

        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('#publication-'.$publicationId, 'Quel choix préférez-vous ?');
        $client->submit($crawler->filter(sprintf('form[action="/communaute/publication/%d/voter"]', $publicationId))->form(['option' => 0]));
        self::assertResponseRedirects('/communaute#publication-'.$publicationId);
        self::assertCount(1, $entityManager->getRepository(VotePublication::class)->findBy(['publication' => $publication]));
        $crawler = $client->followRedirect();
        $client->submit($crawler->filter(sprintf('form[action="/communaute/publication/%d/repondre"]', $publicationId))->form(['contenu' => 'Une réponse communautaire.']));
        self::assertResponseRedirects('/communaute#publication-'.$publicationId);
        self::assertCount(1, $entityManager->getRepository(ReponsePublication::class)->findBy(['publication' => $publication]));

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
