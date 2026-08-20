<?php

namespace App\Tests\Controller;

use App\Entity\CategorieJeu;
use App\Entity\Genre;
use App\Entity\Jeu;
use App\Entity\Langue;
use App\Entity\Plateforme;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PropositionJeuControllerTest extends WebTestCase
{
    public function testUnVisiteurEstRedirigeVersLaConnexion(): void
    {
        $client = self::createClient();
        $client->request('GET', '/jeu/proposer');

        self::assertResponseRedirects('/connexion');
    }

    public function testUnMembrePeutEnvoyerUnePropositionEnAttente(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $nomJeu = 'Mon Jeu Symfony '.$suffixe;
        $utilisateur = (new Utilisateur())->setPseudo('CreateurTest')->setEmail('createur-'.$suffixe.'@glitchworlds.local');
        $categorie = (new CategorieJeu())->setNom('Fangame test')->setSlug('fangame-'.$suffixe);
        $genre = (new Genre())->setNom('RPG test')->setSlug('rpg-'.$suffixe);
        $plateforme = (new Plateforme())->setNom('PC test')->setSlug('pc-'.$suffixe);
        $langue = (new Langue())->setNom('Français test')->setSlug('francais-'.$suffixe);
        foreach ([$utilisateur, $categorie, $genre, $plateforme, $langue] as $entite) {
            $entityManager->persist($entite);
        }
        $entityManager->flush();
        $ids = [
            'utilisateur' => $utilisateur->getId(),
            'categorie' => $categorie->getId(),
            'genre' => $genre->getId(),
            'plateforme' => $plateforme->getId(),
            'langue' => $langue->getId(),
        ];

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/jeu/proposer');
        self::assertSelectorExists('[data-controller="bbcode"]');
        self::assertSelectorExists('button[data-bbcode-id-param="presentation_pokemon"]');
        self::assertSelectorExists('body[data-turbo="false"]');
        self::assertStringContainsString('onbeforeunload', (string) $client->getResponse()->getContent());
        self::assertSelectorExists('input[name="jeu_proposition[miniatureFichier]"]');
        self::assertSelectorExists('input[name="jeu_proposition[banniereFichier]"]');
        self::assertSelectorExists('[data-action="bbcode#video"]');
        self::assertSelectorExists('[data-action="bbcode#tableau"]');
        self::assertSelectorTextContains('[data-action="bbcode#basculerApercu"]', 'Afficher l’aperçu');
        $editeur = $crawler->filter('[data-controller="bbcode"]')->first();
        $client->request('POST', $editeur->attr('data-bbcode-apercu-url-value'), [
            '_token' => $editeur->attr('data-bbcode-jeton-value'),
            'contenu' => '[b]Aperçu Symfony[/b]',
        ]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<strong>Aperçu Symfony</strong>', $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/jeu/proposer');
        $categorieChoisie = $crawler->filter('select[name="jeu_proposition[categorie]"] option')->reduce(
            static fn ($noeud) => $noeud->attr('value') !== '',
        )->first()->attr('value');
        $genreChoisi = $crawler->filter('input[name="jeu_proposition[genres][]"]')->first()->attr('value');
        $plateformeChoisie = $crawler->filter('input[name="jeu_proposition[plateformes][]"]')->first()->attr('value');
        $langueChoisie = $crawler->filter('input[name="jeu_proposition[langues][]"]')->first()->attr('value');
        $miniatureTemporaire = tempnam(sys_get_temp_dir(), 'miniature-test-');
        $banniereTemporaire = tempnam(sys_get_temp_dir(), 'banniere-test-');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        file_put_contents($miniatureTemporaire, $png);
        file_put_contents($banniereTemporaire, $png);
        $form = $crawler->selectButton('Envoyer pour validation')->form([
            'jeu_proposition[nom]' => $nomJeu,
            'jeu_proposition[description]' => 'Une proposition complète créée pendant le test.',
            'jeu_proposition[contenu]' => 'Présentation détaillée du jeu.',
            'jeu_proposition[developpeur]' => 'Équipe Test',
            'jeu_proposition[categorie]' => $categorieChoisie,
            'jeu_proposition[genres]' => [$genreChoisi],
            'jeu_proposition[plateformes]' => [$plateformeChoisie],
            'jeu_proposition[langues]' => [$langueChoisie],
        ]);
        $form['jeu_proposition[miniatureFichier]']->upload($miniatureTemporaire);
        $form['jeu_proposition[banniereFichier]']->upload($banniereTemporaire);
        $client->submit($form);

        self::assertResponseRedirects('/mon-compte');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $jeu = $entityManager->getRepository(Jeu::class)->findOneBy(['nom' => $nomJeu]);
        self::assertInstanceOf(Jeu::class, $jeu);
        self::assertSame(StatutJeu::EnAttente, $jeu->getStatut());
        self::assertSame($ids['utilisateur'], $jeu->getCreateur()?->getId());
        self::assertSame('mon-jeu-symfony-'.$suffixe, $jeu->getSlug());
        self::assertStringStartsWith('miniature.', (string) $jeu->getMiniature());
        self::assertStringStartsWith('banniere.', (string) $jeu->getBanniere());
        self::assertFileExists(self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/jeux/'.$jeu->getId().'/'.$jeu->getMiniature());
        self::assertFileExists(self::getContainer()->getParameter('kernel.project_dir').'/public/uploads/jeux/'.$jeu->getId().'/'.$jeu->getBanniere());
        self::assertCount(1, $jeu->getGenres());
        $jeuId = $jeu->getId();

        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('body', $nomJeu);
        $crawler = $client->click($crawler->selectLink('Modifier la proposition')->link());
        $client->submit($crawler->selectButton('Enregistrer les modifications')->form([
            'jeu_proposition[nom]' => $nomJeu.' Modifié',
        ]));
        self::assertResponseRedirects('/mon-compte');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $jeu = $entityManager->find(Jeu::class, $jeuId);
        self::assertSame($nomJeu.' Modifié', $jeu->getNom());
        self::assertSame(StatutJeu::EnAttente, $jeu->getStatut());
        $entityManager->remove($jeu);
        $entityManager->flush();

        @unlink($miniatureTemporaire);
        @unlink($banniereTemporaire);

        foreach ([Utilisateur::class => 'utilisateur', CategorieJeu::class => 'categorie', Genre::class => 'genre', Plateforme::class => 'plateforme', Langue::class => 'langue'] as $classe => $cle) {
            $entityManager->remove($entityManager->find($classe, $ids[$cle]));
        }
        $entityManager->flush();
    }
}
