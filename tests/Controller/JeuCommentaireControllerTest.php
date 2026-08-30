<?php

namespace App\Tests\Controller;

use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JeuCommentaireControllerTest extends WebTestCase
{
    public function testLesCommentairesSontPaginesDixParDix(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $jeu = (new Jeu())
            ->setNom('Jeu pagination '.$suffixe)
            ->setSlug('jeu-pagination-'.$suffixe)
            ->setDescription('Jeu utilisé pour tester la pagination.')
            ->setStatut(StatutJeu::Approuve);
        $entityManager->persist($jeu);
        for ($numero = 1; $numero <= 11; ++$numero) {
            $entityManager->persist(
                (new CommentaireJeu())
                    ->setJeu($jeu)
                    ->setContenu('Commentaire pagination '.$numero)
                    ->setDateCommentaire(new \DateTimeImmutable(sprintf('2026-01-%02d 12:00:00', $numero))),
            );
        }
        $entityManager->flush();
        $jeuId = $jeu->getId();

        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeu->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#commentaires', 'Commentaire pagination 11');
        self::assertCount(10, $crawler->filter('#commentaires .vstack.gap-3 > .gw-comment'));
        self::assertSelectorTextContains('#commentaires .pagination', '1 / 2');

        $crawler = $client->request('GET', sprintf('/jeu/%s-%d?commentaires_page=2', $jeu->getSlug(), $jeu->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#commentaires', 'Commentaire pagination 1');
        self::assertCount(1, $crawler->filter('#commentaires .vstack.gap-3 > .gw-comment'));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->flush();
    }

    public function testUnUtilisateurConnectePeutCommenterLeJeuAffiche(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));

        $utilisateur = (new Utilisateur())
            ->setPseudo('AuteurTest')
            ->setEmail(sprintf('auteur-%s@glitchworlds.local', $suffixe));
        $jeu = (new Jeu())
            ->setNom('Jeu de test')
            ->setSlug('jeu-test-'.$suffixe)
            ->setDescription('Jeu utilisé par un test fonctionnel.')
            ->setStatut(StatutJeu::Approuve);

        $entityManager->persist($utilisateur);
        $entityManager->persist($jeu);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();
        $jeuId = $jeu->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeu->getId()));
        $client->submit($crawler->filter('#commentaire_jeu_publier')->form([
            'commentaire_jeu[contenu]' => 'Commentaire créé par le test Symfony.',
        ]));

        self::assertResponseRedirects(sprintf('/jeu/%s-%d#commentaires', $jeu->getSlug(), $jeu->getId()));

        $commentaire = $entityManager->getRepository(CommentaireJeu::class)->findOneBy([
            'jeu' => $jeu,
            'auteur' => $utilisateur,
        ]);
        self::assertInstanceOf(CommentaireJeu::class, $commentaire);
        self::assertSame('Commentaire créé par le test Symfony.', $commentaire->getContenu());
        $commentaireId = $commentaire->getId();

        $crawler = $client->followRedirect();
        $client->submit($crawler->selectButton('Publier la réponse')->form([
            'contenu' => 'Réponse créée par le test Symfony.',
        ]));
        self::assertResponseRedirects(sprintf('/jeu/%s-%d#commentaire-%d', $jeu->getSlug(), $jeu->getId(), $commentaireId));

        $reponse = $entityManager->getRepository(CommentaireJeu::class)->findOneBy([
            'parent' => $commentaire,
        ]);
        self::assertInstanceOf(CommentaireJeu::class, $reponse);
        self::assertSame($jeu->getId(), $reponse->getJeu()?->getId());
        self::assertSame('Réponse créée par le test Symfony.', $reponse->getContenu());

        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('.gw-comment-replies', 'Réponse créée par le test Symfony.');
        $crawler = $client->click($crawler->selectLink('Modifier')->link());
        $client->submit($crawler->selectButton('Enregistrer')->form([
            'commentaire_jeu[contenu]' => 'Commentaire modifié par le test Symfony.',
        ]));
        self::assertResponseRedirects(sprintf('/jeu/%s-%d#commentaires', $jeu->getSlug(), $jeu->getId()));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertSame('Commentaire modifié par le test Symfony.', $entityManager->find(CommentaireJeu::class, $commentaireId)->getContenu());

        $crawler = $client->followRedirect();
        $client->submit($crawler->selectButton('Supprimer')->form());
        self::assertResponseRedirects(sprintf('/jeu/%s-%d#commentaires', $jeu->getSlug(), $jeu->getId()));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertNull($entityManager->find(CommentaireJeu::class, $commentaireId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }
}
