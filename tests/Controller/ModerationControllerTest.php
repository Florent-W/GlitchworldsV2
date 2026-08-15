<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Entity\CommentaireActualite;
use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ModerationControllerTest extends WebTestCase
{
    public function testUnModerateurPeutModifierEtSupprimerDesCommentaires(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $moderateur = (new Utilisateur())->setPseudo('ModerateurCommentaires')->setEmail('moderateur-commentaires-'.$suffixe.'@glitchworlds.local')->setRoles(['ROLE_MODERATEUR']);
        $auteur = (new Utilisateur())->setPseudo('AuteurCommentaires')->setEmail('auteur-commentaires-'.$suffixe.'@glitchworlds.local');
        $jeu = (new Jeu())->setNom('Jeu commenté')->setSlug('jeu-commente-'.$suffixe)->setDescription('Jeu utilisé pour tester la modération.')->setStatut(StatutJeu::Approuve);
        $actualite = (new Actualite())->setTitre('Actualité commentée')->setSlug('actualite-commentee-'.$suffixe)->setDescription('Actualité utilisée pour tester la modération.')->setContenu('Contenu de test.');
        $commentaireJeu = (new CommentaireJeu())->setJeu($jeu)->setAuteur($auteur)->setContenu('Commentaire de jeu à modérer.');
        $commentaireActualite = (new CommentaireActualite())->setActualite($actualite)->setAuteur($auteur)->setContenu('Commentaire d’actualité à supprimer.');
        foreach ([$moderateur, $auteur, $jeu, $actualite, $commentaireJeu, $commentaireActualite] as $entite) { $entityManager->persist($entite); }
        $entityManager->flush();
        $commentaireJeuId = $commentaireJeu->getId();
        $commentaireActualiteId = $commentaireActualite->getId();
        $jeuId = $jeu->getId();
        $actualiteId = $actualite->getId();
        $moderateurId = $moderateur->getId();
        $auteurId = $auteur->getId();

        $client->loginUser($moderateur);
        $crawler = $client->request('GET', '/moderation/commentaires');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Commentaire de jeu à modérer.');
        self::assertSelectorTextContains('body', 'Commentaire d’actualité à supprimer.');

        $crawler = $client->click($crawler->filter(sprintf('a[href="/commentaire/%d/modifier?retour=moderation"]', $commentaireJeuId))->link());
        $client->submit($crawler->selectButton('Enregistrer')->form(['commentaire_jeu[contenu]' => 'Commentaire corrigé par la modération.']));
        self::assertResponseRedirects('/moderation/commentaires');

        $crawler = $client->followRedirect();
        $client->submit($crawler->filter(sprintf('form[action="/actualite/commentaire/%d/supprimer"]', $commentaireActualiteId))->form());
        self::assertResponseRedirects('/moderation/commentaires');

        $entityManager->clear();
        self::assertSame('Commentaire corrigé par la modération.', $entityManager->find(CommentaireJeu::class, $commentaireJeuId)?->getContenu());
        self::assertNull($entityManager->find(CommentaireActualite::class, $commentaireActualiteId));

        $entityManager->remove($entityManager->find(CommentaireJeu::class, $commentaireJeuId));
        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($entityManager->find(Actualite::class, $actualiteId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $auteurId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $moderateurId));
        $entityManager->flush();
    }

    public function testUnMembreSimpleNePeutPasAccederALaModeration(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = (new Utilisateur())->setPseudo('MembreSimple')->setEmail('simple-'.bin2hex(random_bytes(5)).'@glitchworlds.local');
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $id = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $client->request('GET', '/moderation/jeux');
        self::assertResponseStatusCodeSame(403);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Utilisateur::class, $id));
        $entityManager->flush();
    }

    public function testUnModerateurPeutApprouverUneProposition(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $moderateur = (new Utilisateur())
            ->setPseudo('ModerateurTest')
            ->setEmail('moderateur-'.$suffixe.'@glitchworlds.local')
            ->setRoles(['ROLE_MODERATEUR']);
        $jeu = (new Jeu())
            ->setNom('Proposition à approuver')
            ->setSlug('proposition-approuver-'.$suffixe)
            ->setDescription('Cette fiche doit devenir publique.')
            ->setCreateur($moderateur)
            ->setStatut(StatutJeu::EnAttente);
        $entityManager->persist($moderateur);
        $entityManager->persist($jeu);
        $entityManager->flush();
        $moderateurId = $moderateur->getId();
        $jeuId = $jeu->getId();

        $client->loginUser($moderateur);
        $crawler = $client->request('GET', '/moderation/jeux');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Proposition à approuver');

        $client->request('GET', sprintf('/moderation/jeux/%d', $jeuId));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Proposition à approuver');
        self::assertSelectorExists(sprintf('form[action="/moderation/jeux/%d/approuver"]', $jeuId));

        $crawler = $client->request('GET', '/moderation/jeux');
        $client->submit($crawler->filter(sprintf('form[action="/moderation/jeux/%d/approuver"]', $jeuId))->form());
        self::assertResponseRedirects('/moderation/jeux');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $jeu = $entityManager->find(Jeu::class, $jeuId);
        self::assertSame(StatutJeu::Approuve, $jeu->getStatut());

        $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        self::assertResponseIsSuccessful();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $moderateurId));
        $entityManager->flush();
    }
}
