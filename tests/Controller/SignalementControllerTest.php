<?php

namespace App\Tests\Controller;

use App\Entity\Jeu;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Enum\StatutSignalement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SignalementControllerTest extends WebTestCase
{
    public function testUnMembrePeutSignalerUnJeuEtUnModerateurLeTraiter(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $membre = (new Utilisateur())->setPseudo('Signaleur'.$suffixe)->setEmail('signaleur-'.$suffixe.'@test.local');
        $moderateur = (new Utilisateur())->setPseudo('Moderateur'.$suffixe)->setEmail('moderateur-'.$suffixe.'@test.local')->setRoles(['ROLE_MODERATEUR']);
        $jeu = (new Jeu())->setNom('Jeu signalé '.$suffixe)->setSlug('jeu-signale-'.$suffixe)->setDescription('Une fiche à examiner par la modération.')->setStatut(StatutJeu::Approuve);
        foreach ([$membre, $moderateur, $jeu] as $entite) { $entityManager->persist($entite); }
        $entityManager->flush();
        $ids = ['membre' => $membre->getId(), 'moderateur' => $moderateur->getId(), 'jeu' => $jeu->getId()];

        $client->loginUser($membre);
        $crawler = $client->request('GET', '/signaler/jeu/'.$jeu->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Signaler un contenu');
        $client->submit($crawler->selectButton('Envoyer le signalement')->form([
            'signalement[motif]' => 'fausses_informations',
            'signalement[details]' => 'Les informations de cette fiche semblent incorrectes.',
        ]));
        self::assertResponseRedirects('/jeu/'.$jeu->getSlug().'-'.$jeu->getId());

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['jeu' => $jeu]);
        self::assertInstanceOf(Signalement::class, $signalement);
        self::assertSame(StatutSignalement::EnAttente, $signalement->getStatut());
        $signalementId = $signalement->getId();

        $client->loginUser($moderateur);
        $crawler = $client->request('GET', '/moderation/signalements');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Jeu signalé '.$suffixe);
        $client->submit($crawler->filter(sprintf('form[action="/moderation/signalements/%d/traiter"]', $signalementId))->form());
        self::assertResponseRedirects('/moderation/signalements');

        $entityManager->clear();
        self::assertSame(StatutSignalement::Traite, $entityManager->find(Signalement::class, $signalementId)?->getStatut());
        $entityManager->remove($entityManager->find(Signalement::class, $signalementId));
        $entityManager->remove($entityManager->find(Jeu::class, $ids['jeu']));
        $entityManager->remove($entityManager->find(Utilisateur::class, $ids['membre']));
        $entityManager->remove($entityManager->find(Utilisateur::class, $ids['moderateur']));
        $entityManager->flush();
    }

    public function testUnAdministrateurPeutFiltrerChangerLeStatutEtSupprimerUnJeu(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $admin = (new Utilisateur())->setPseudo('AdminJeux'.$suffixe)->setEmail('admin-jeux-'.$suffixe.'@test.local')->setRoles(['ROLE_ADMIN']);
        $jeu = (new Jeu())->setNom('Administration '.$suffixe)->setSlug('administration-'.$suffixe)->setDescription('Jeu administré pendant le test.')->setStatut(StatutJeu::Approuve);
        $entityManager->persist($admin); $entityManager->persist($jeu); $entityManager->flush();
        $adminId = $admin->getId(); $jeuId = $jeu->getId();

        $client->loginUser($admin);
        $crawler = $client->request('GET', '/moderation/jeux?recherche='.$suffixe.'&statut=approuve');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Administration '.$suffixe);
        $form = $crawler->filter(sprintf('form[action="/moderation/jeux/%d/statut"]', $jeuId))->form();
        $client->submit($form, ['statut' => 'brouillon']);
        self::assertResponseRedirects('/moderation/jeux');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertSame(StatutJeu::Brouillon, $entityManager->find(Jeu::class, $jeuId)?->getStatut());

        $crawler = $client->request('GET', '/moderation/jeux?recherche='.$suffixe);
        $client->submit($crawler->filter(sprintf('form[action="/moderation/jeux/%d/supprimer"]', $jeuId))->form());
        self::assertResponseRedirects('/moderation/jeux');
        $entityManager->clear();
        self::assertNull($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $adminId));
        $entityManager->flush();
    }
}
