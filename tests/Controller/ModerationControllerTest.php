<?php

namespace App\Tests\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ModerationControllerTest extends WebTestCase
{
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
