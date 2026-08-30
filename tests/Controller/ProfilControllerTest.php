<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfilControllerTest extends WebTestCase
{
    public function testLeProfilEstPublicSansAfficherEmail(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Profil'.$suffixe)->setEmail('prive-'.$suffixe.'@test.local')->setBiographie('Une présentation publique.');
        $entityManager->persist($membre); $entityManager->flush();

        $client->request('GET', '/membre/'.$membre->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Profil'.$suffixe);
        self::assertSelectorTextContains('body', 'Une présentation publique.');
        self::assertSelectorTextNotContains('body', 'prive-'.$suffixe.'@test.local');

        $entityManager->remove($membre); $entityManager->flush();
    }

    public function testUnMembreVoitUnBoutonPourChangerSonAvatarSurSonProfil(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('AvatarProfil'.$suffixe)->setEmail('avatar-'.$suffixe.'@test.local');
        $entityManager->persist($membre);
        $entityManager->flush();
        $membreId = $membre->getId();

        $client->loginUser($membre);
        $client->request('GET', '/membre/'.$membreId);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="avatar-profil"]');
        self::assertSelectorExists('form[action="/mon-compte/avatar"] input[name="avatar"]');
        self::assertSelectorExists('button[aria-label="Changer la photo de profil"]');

        $entityManager->remove($membre);
        $entityManager->flush();
    }

    public function testUnMembreNeVoitPasLeChangementDAvatarSurLeProfilDUnAutre(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Cible'.$suffixe)->setEmail('cible-'.$suffixe.'@test.local');
        $visiteur = (new Utilisateur())->setPseudo('Visiteur'.$suffixe)->setEmail('visiteur-'.$suffixe.'@test.local');
        $entityManager->persist($membre);
        $entityManager->persist($visiteur);
        $entityManager->flush();
        $membreId = $membre->getId();

        $client->loginUser($visiteur);
        $client->request('GET', '/membre/'.$membreId);

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-controller="avatar-profil"]');
        self::assertSelectorNotExists('form[action="/mon-compte/avatar"]');

        $entityManager->remove($visiteur);
        $entityManager->remove($membre);
        $entityManager->flush();
    }

    public function testUnMembrePeutSuivrePuisNePlusSuivreUnAutreMembre(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $abonne = (new Utilisateur())->setPseudo('Abonne'.$suffixe)->setEmail('abonne-'.$suffixe.'@test.local');
        $suivi = (new Utilisateur())->setPseudo('Suivi'.$suffixe)->setEmail('suivi-'.$suffixe.'@test.local');
        $entityManager->persist($abonne); $entityManager->persist($suivi); $entityManager->flush();
        $abonneId = $abonne->getId(); $suiviId = $suivi->getId();

        $client->loginUser($abonne);
        $crawler = $client->request('GET', '/membre/'.$suiviId);
        $client->submit($crawler->selectButton('Suivre')->form());
        self::assertResponseRedirects('/membre/'.$suiviId);
        $entityManager->clear();
        self::assertTrue($entityManager->find(Utilisateur::class, $abonneId)->suit($entityManager->find(Utilisateur::class, $suiviId)));

        $crawler = $client->request('GET', '/membre/'.$suiviId);
        $client->submit($crawler->selectButton('Ne plus suivre')->form());
        $entityManager->clear();
        self::assertFalse($entityManager->find(Utilisateur::class, $abonneId)->suit($entityManager->find(Utilisateur::class, $suiviId)));

        $entityManager->remove($entityManager->find(Utilisateur::class, $abonneId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $suiviId));
        $entityManager->flush();
    }

    public function testLeProfilAfficheLesListesDUnAutreMembre(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $auteur = (new Utilisateur())->setPseudo('Listes'.$suffixe)->setEmail('listes-'.$suffixe.'@test.local');
        $visiteur = (new Utilisateur())->setPseudo('VisiteurListes'.$suffixe)->setEmail('visiteur-listes-'.$suffixe.'@test.local');
        $jeu = (new \App\Entity\Jeu())
            ->setNom('Jeu de liste publique')
            ->setSlug('jeu-liste-publique-'.$suffixe)
            ->setDescription('Visible dans une liste de profil.')
            ->setStatut(\App\Enum\StatutJeu::Approuve);
        $liste = (new \App\Entity\ListeJeux())
            ->setUtilisateur($auteur)
            ->setNom('Fangames RPG')
            ->setDescription('Mes recommandations');
        $liste->ajouterJeu($jeu);

        $entityManager->persist($auteur);
        $entityManager->persist($visiteur);
        $entityManager->persist($jeu);
        $entityManager->persist($liste);
        $entityManager->flush();
        $auteurId = $auteur->getId();

        $client->loginUser($visiteur);
        $client->request('GET', '/membre/'.$auteurId.'?section=listes');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Listes de jeux');
        self::assertSelectorTextContains('body', 'Fangames RPG');
        self::assertSelectorTextContains('body', 'Jeu de liste publique');

        $entityManager->remove($liste);
        $entityManager->remove($jeu);
        $entityManager->remove($visiteur);
        $entityManager->remove($auteur);
        $entityManager->flush();
    }

    public function testUnMembrePeutModifierSaBiographieDansAPropos(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('BioProfil'.$suffixe)->setEmail('bio-'.$suffixe.'@test.local');
        $entityManager->persist($membre);
        $entityManager->flush();
        $membreId = $membre->getId();

        $client->loginUser($membre);
        $crawler = $client->request('GET', '/membre/'.$membreId.'?section=apropos');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/membre/'.$membreId.'/biographie"] textarea[name="biographie_profil[biographie]"]');

        $client->submit($crawler->selectButton('Enregistrer')->form([
            'biographie_profil[biographie]' => 'Ma nouvelle présentation publique.',
        ]));
        self::assertResponseRedirects('/membre/'.$membreId.'?section=apropos');

        $entityManager->clear();
        self::assertSame('Ma nouvelle présentation publique.', $entityManager->find(Utilisateur::class, $membreId)?->getBiographie());

        $client->followRedirect();
        self::assertSelectorTextContains('textarea[name="biographie_profil[biographie]"]', 'Ma nouvelle présentation publique.');

        $entityManager->remove($entityManager->find(Utilisateur::class, $membreId));
        $entityManager->flush();
    }

    public function testLeProfilAfficheLesActualitesPubliees(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('AuteurActuProfil'.$suffixe)->setEmail('auteur-profil-'.$suffixe.'@test.local');
        $actualite = (new \App\Entity\Actualite())
            ->setTitre('Actualité publiée '.$suffixe)
            ->setSlug('actualite-publiee-'.$suffixe)
            ->setDescription('Visible sur le profil public.')
            ->setContenu('Contenu de test.')
            ->setAuteur($membre)
            ->setStatut(\App\Enum\StatutActualite::Publiee);
        $entityManager->persist($membre);
        $entityManager->persist($actualite);
        $entityManager->flush();
        $membreId = $membre->getId();
        $actualiteId = $actualite->getId();

        $client->request('GET', '/membre/'.$membreId.'?section=actualites');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Actualités');
        self::assertSelectorTextContains('body', 'Actualité publiée '.$suffixe);

        $entityManager->remove($entityManager->find(\App\Entity\Actualite::class, $actualiteId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $membreId));
        $entityManager->flush();
    }
}
