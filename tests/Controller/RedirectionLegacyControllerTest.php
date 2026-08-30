<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Enum\StatutActualite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RedirectionLegacyControllerTest extends WebTestCase
{
    public function testAncienneActualiteRedirigeEn301VersSonUrlCanonique(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(4));
        $actualite = (new Actualite())
            ->setTitre('Actualité legacy')
            ->setSlug('actualite-legacy-'.$suffixe)
            ->setDescription('Actualité utilisée pour tester une redirection permanente.')
            ->setContenu('Contenu de test.')
            ->setStatut(StatutActualite::Publiee);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($actualite);
        $em->flush();

        $client->request('GET', '/news/ancien-slug-'.$actualite->getId());

        self::assertResponseStatusCodeSame(301);
        self::assertResponseRedirects('/actualite/'.$actualite->getSlug().'-'.$actualite->getId(), 301);
        $em->remove($actualite);
        $em->flush();
    }

    public function testAnciennesPagesDeListeRedirigentEn301(): void
    {
        $client = self::createClient();

        $client->request('GET', '/articles/glitchs');
        self::assertResponseRedirects('/actualites/glitchs', 301);

        $client->request('GET', '/liste/Jeux/Rom+hacks');
        self::assertResponseRedirects('/jeux?categorie=rom-hacks', 301);

        $client->request('GET', '/index.php');
        self::assertResponseRedirects('/', 301);

        $client->request('GET', '/recherche.php?recherche=&categorie=Jeux&categorie_jeu=Officiel&page=2');
        self::assertResponseRedirects('/jeux?categorie=officiels&page=2', 301);

        $client->request('GET', '/creation_news.php');
        self::assertResponseRedirects('/actualite/proposer', 301);
    }
}
