<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SecuriteControllerTest extends WebTestCase
{
    public function testLaPageDeConnexionEstAccessible(): void
    {
        $client = self::createClient();
        $client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bon retour');
        self::assertSelectorExists('input[name="_csrf_token"]');
        self::assertSelectorExists('meta[name="robots"][content="noindex,nofollow"]');
    }

    public function testLaPageInscriptionContientLeFormulaireSymfony(): void
    {
        $client = self::createClient();
        $client->request('GET', '/inscription');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Créer un compte');
        self::assertSelectorExists('input[name="inscription[email]"]');
        self::assertSelectorExists('input[name="inscription[motDePasseClair][first]"]');
        self::assertSelectorExists('input[name="inscription[_token]"]');
    }

    public function testUneInscriptionEnregistreUnMotDePasseHache(): void
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/inscription');
        $email = sprintf('test-%s@glitchworlds.local', bin2hex(random_bytes(6)));

        $client->submit($crawler->selectButton('Créer mon compte')->form([
            'inscription[pseudo]' => 'TesteurSymfony',
            'inscription[email]' => $email,
            'inscription[motDePasseClair][first]' => 'mot-de-passe-test-2026',
            'inscription[motDePasseClair][second]' => 'mot-de-passe-test-2026',
        ]));

        self::assertResponseRedirects('/connexion');

        /** @var UtilisateurRepository $utilisateurs */
        $utilisateurs = self::getContainer()->get(UtilisateurRepository::class);
        $utilisateur = $utilisateurs->findOneBy(['email' => $email]);
        self::assertInstanceOf(Utilisateur::class, $utilisateur);
        self::assertNotSame('mot-de-passe-test-2026', $utilisateur->getPassword());

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($utilisateur, 'mot-de-passe-test-2026'));

        $utilisateurs->getEntityManager()->remove($utilisateur);
        $utilisateurs->getEntityManager()->flush();
    }

    public function testUnVisiteurEstRedirigeVersLaConnexionPourVoirSonCompte(): void
    {
        $client = self::createClient();
        $client->request('GET', '/mon-compte');

        self::assertResponseRedirects('/connexion');
    }

    public function testUnMembreConnectePeutVoirSonCompte(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = (new Utilisateur())
            ->setPseudo('MembreTest')
            ->setEmail(sprintf('compte-%s@glitchworlds.local', bin2hex(random_bytes(5))));
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $client->request('GET', '/mon-compte');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'MembreTest');
        self::assertSelectorExists('a[href="/deconnexion"]');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }

    public function testUnMembrePeutModifierSonPseudoEtSonEmail(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $utilisateur = (new Utilisateur())
            ->setPseudo('AncienPseudo')
            ->setEmail(sprintf('ancien-%s@glitchworlds.local', $suffixe));
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/mon-compte/modifier');
        $client->submit($crawler->selectButton('Enregistrer les modifications')->form([
            'compte[pseudo]' => 'NouveauPseudo',
            'compte[email]' => sprintf('NOUVEAU-%s@GLITCHWORLDS.LOCAL', $suffixe),
        ]));

        self::assertResponseRedirects('/mon-compte');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        self::assertSame('NouveauPseudo', $utilisateur->getPseudo());
        self::assertSame(sprintf('nouveau-%s@glitchworlds.local', $suffixe), $utilisateur->getEmail());

        $entityManager->remove($utilisateur);
        $entityManager->flush();
    }

    public function testUnMembrePeutChangerSonMotDePasse(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $utilisateur = (new Utilisateur())
            ->setPseudo('MotDePasseTest')
            ->setEmail(sprintf('mot-de-passe-%s@glitchworlds.local', bin2hex(random_bytes(5))));
        $utilisateur->setPassword($hasher->hashPassword($utilisateur, 'ancien-mot-de-passe-2026'));
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', '/mon-compte/mot-de-passe');
        $client->submit($crawler->selectButton('Changer le mot de passe')->form([
            'mot_de_passe[motDePasseActuel]' => 'ancien-mot-de-passe-2026',
            'mot_de_passe[nouveauMotDePasse][first]' => 'nouveau-mot-de-passe-2026',
            'mot_de_passe[nouveauMotDePasse][second]' => 'nouveau-mot-de-passe-2026',
        ]));

        self::assertResponseRedirects('/mon-compte');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($utilisateur, 'nouveau-mot-de-passe-2026'));
        self::assertFalse($hasher->isPasswordValid($utilisateur, 'ancien-mot-de-passe-2026'));

        $entityManager->remove($utilisateur);
        $entityManager->flush();
    }
}
