<?php

namespace App\Tests\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class JeuVideoBackgroundControllerTest extends WebTestCase
{
    public function testLaVideoEstRendueAvantLeShellApplicatif(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $videoUrl = 'https://www.youtube.com/watch?v=Bra1wzWQQ0A';

        $jeu = (new Jeu())
            ->setNom('Jeu vidéo test')
            ->setSlug('jeu-video-test-'.$suffixe)
            ->setDescription('Jeu utilisé pour tester la vidéo de fond.')
            ->setStatut(StatutJeu::Approuve)
            ->setVideoBackground($videoUrl);

        $entityManager->persist($jeu);
        $entityManager->flush();

        $crawler = $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeu->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body.gw-has-video-background');
        self::assertSelectorExists('#bgndVideo.player');
        self::assertSelectorExists('#btnPlay.gw-ytplayer-controls__btn');
        self::assertSelectorExists('#btnPleinEcran.gw-ytplayer-controls__btn');
        self::assertStringContainsString('Bra1wzWQQ0A', (string) $crawler->filter('#bgndVideo')->attr('data-property'));
        self::assertSelectorExists('link[href*="jquery.mb.YTPlayer"]');
        self::assertSelectorExists('[data-controller~="navigation-sequentielle"]');

        $html = $client->getResponse()->getContent();
        self::assertNotFalse($html);
        self::assertLessThan(
            strpos($html, 'class="gw-app"'),
            strpos($html, 'id="bgndVideo"'),
        );

        $entityManager->remove($jeu);
        $entityManager->flush();
    }

    public function testLaVideoEstMasqueeQuandLeMembreDesactiveLaPreference(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));

        $utilisateur = (new Utilisateur())
            ->setPseudo('ViewerVideo')
            ->setEmail(sprintf('viewer-video-%s@glitchworlds.local', $suffixe))
            ->setVideoBackgroundActive(false);

        $jeu = (new Jeu())
            ->setNom('Jeu vidéo privé')
            ->setSlug('jeu-video-prive-'.$suffixe)
            ->setDescription('Jeu utilisé pour tester la préférence vidéo.')
            ->setStatut(StatutJeu::Approuve)
            ->setVideoBackground('https://www.youtube.com/watch?v=Bra1wzWQQ0A');

        $entityManager->persist($utilisateur);
        $entityManager->persist($jeu);
        $entityManager->flush();

        $client->loginUser($utilisateur);
        $client->request('GET', sprintf('/jeu/%s-%d', $jeu->getSlug(), $jeu->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('body.gw-has-video-background');
        self::assertSelectorNotExists('#bgndVideo');

        $entityManager->remove($jeu);
        $entityManager->remove($utilisateur);
        $entityManager->flush();
    }
}
