<?php

namespace App\Tests\Controller;

use App\Entity\Avis;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NoteJeuControllerTest extends WebTestCase
{
    public function testUnMembreAjoutePuisModifieSaNoteSansCreerDeDoublon(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $utilisateur = (new Utilisateur())
            ->setPseudo('NoteTest')
            ->setEmail(sprintf('note-%s@glitchworlds.local', $suffixe));
        $jeu = (new Jeu())
            ->setNom('Jeu noté de test')
            ->setSlug('jeu-note-'.$suffixe)
            ->setDescription('Jeu utilisé pour tester les notes.')
            ->setStatut(StatutJeu::Approuve);
        $entityManager->persist($utilisateur);
        $entityManager->persist($jeu);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();
        $jeuId = $jeu->getId();

        $client->loginUser($utilisateur);
        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeuId));
        $client->submit($crawler->selectButton('Publier mon avis')->form([
            'note_jeu[note]' => 4,
        ]));
        self::assertResponseRedirects(sprintf('/jeu/%s-%d#avis-joueurs', $jeu->getSlug(), $jeuId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $avis = $entityManager->getRepository(Avis::class)->findOneBy(['jeu' => $jeuId, 'auteur' => $utilisateurId]);
        self::assertInstanceOf(Avis::class, $avis);
        self::assertSame(4.0, $avis->getNote());
        $avisId = $avis->getId();

        $crawler = $client->followRedirect();
        $client->submit($crawler->selectButton('Modifier mon avis')->form([
            'note_jeu[note]' => 5,
        ]));
        self::assertResponseRedirects(sprintf('/jeu/%s-%d#avis-joueurs', $jeu->getSlug(), $jeuId));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        self::assertSame(5.0, $entityManager->find(Avis::class, $avisId)->getNote());
        self::assertSame(1, $entityManager->getRepository(Avis::class)->count(['jeu' => $jeuId, 'auteur' => $utilisateurId]));

        $entityManager->remove($entityManager->find(Jeu::class, $jeuId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }
}
