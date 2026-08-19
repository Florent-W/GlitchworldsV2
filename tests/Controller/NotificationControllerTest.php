<?php

namespace App\Tests\Controller;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NotificationControllerTest extends WebTestCase
{
    public function testUnVisiteurNAccedePasAuPanneau(): void
    {
        $client = self::createClient();
        $client->request('GET', '/notifications/panneau');

        self::assertResponseRedirects('/connexion');
    }

    public function testLePanneauListeLesDernieresNotifications(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = (new Utilisateur())
            ->setPseudo('PanneauTest')
            ->setEmail(sprintf('panneau-%s@glitchworlds.local', bin2hex(random_bytes(5))));
        $notification = (new Notification())
            ->setUtilisateur($utilisateur)
            ->setTitre('Succès débloqué')
            ->setMessage('Tu viens de débloquer un nouveau succès.')
            ->setIcone('trophy-fill');
        $entityManager->persist($utilisateur);
        $entityManager->persist($notification);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $client->request('GET', '/notifications/panneau');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.gw-notif-panel__count', '1 non lue');
        self::assertSelectorTextContains('.gw-notif-panel__label', 'Succès débloqué');
        self::assertSelectorExists('.gw-notif-panel__item.is-unread');
        self::assertSelectorExists(sprintf('form[action="/notifications/%d/lire"]', $notification->getId()));
        self::assertSelectorExists('form[action="/notifications/tout-lire"]');
        self::assertSelectorExists('a[href="/notifications"]');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }

    public function testLaClocheOuvreLePanneauEnDeroulant(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = (new Utilisateur())
            ->setPseudo('ClocheTest')
            ->setEmail(sprintf('cloche-%s@glitchworlds.local', bin2hex(random_bytes(5))));
        $entityManager->persist($utilisateur);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $client->loginUser($utilisateur);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="notifications"][data-notifications-url-value="/notifications/panneau"]');
        self::assertSelectorExists('[data-action="notifications#basculer"][aria-expanded="false"]');
        self::assertSelectorExists('#gw-notif-panel[data-notifications-target="panneau"]');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }
}
