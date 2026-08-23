<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PageLegaleControllerTest extends WebTestCase
{
    public function testLaPolitiqueDeConfidentialiteEstAccessible(): void
    {
        $client = self::createClient();
        $client->request('GET', '/politique-de-confidentialite');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Politique de confidentialité');
    }

    public function testLesMentionsLegalesSontAccessibles(): void
    {
        $client = self::createClient();
        $client->request('GET', '/mentions-legales');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mentions légales');
    }

    public function testLesConditionsDUtilisationSontAccessibles(): void
    {
        $client = self::createClient();
        $client->request('GET', '/conditions-utilisation');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Conditions d’utilisation');
    }

    public function testLaCharteDeModerationEstAccessible(): void
    {
        $client = self::createClient();
        $client->request('GET', '/charte-moderation');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Charte de contribution');
    }

    public function testLePiedLegalEstVisibleSurLAccueil(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('nav.gw-legal-footer a[href="/mentions-legales"]');
        self::assertSelectorExists('nav.gw-legal-footer button[data-controller="consentement-publicitaire"]');
    }
}
