<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Enum\StatutActualite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapControllerTest extends WebTestCase
{
    public function testLeSitemapContientLesActualitesPublieesUniquement(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(5));
        $publiee = (new Actualite())->setTitre('Publiée')->setSlug('sitemap-publiee-'.$suffixe)->setDescription('Visible dans le sitemap.')->setContenu('Contenu publié.')->setStatut(StatutActualite::Publiee);
        $brouillon = (new Actualite())->setTitre('Brouillon')->setSlug('sitemap-brouillon-'.$suffixe)->setDescription('Invisible dans le sitemap.')->setContenu('Contenu privé.')->setStatut(StatutActualite::Brouillon);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($publiee);
        $entityManager->persist($brouillon);
        $entityManager->flush();

        $client->request('GET', '/sitemap.xml');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');
        self::assertStringContainsString('/actualite/sitemap-publiee-'.$suffixe.'-'.$publiee->getId(), (string) $client->getResponse()->getContent());
        self::assertStringNotContainsString('/actualite/sitemap-brouillon-'.$suffixe.'-'.$brouillon->getId(), (string) $client->getResponse()->getContent());

        $entityManager->remove($publiee);
        $entityManager->remove($brouillon);
        $entityManager->flush();
    }
}
