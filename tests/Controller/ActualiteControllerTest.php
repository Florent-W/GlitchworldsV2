<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ActualiteControllerTest extends WebTestCase
{
    public function testUneActualiteAfficheSonContenuBbcode(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(5));
        $actualite = (new Actualite())
            ->setTitre('Article BBCode '.$suffixe)
            ->setSlug('article-bbcode-'.$suffixe)
            ->setDescription('Vérifie le rendu sécurisé du BBCode.')
            ->setContenu('[b]Information importante[/b]\n[liste][elementliste]Premier point[/elementliste][/liste]\n<script>alert(1)</script>')
            ->setCategorie(CategorieActualite::News)
            ->setStatut(StatutActualite::Publiee);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($actualite);
        $entityManager->flush();

        $client->request('GET', sprintf('/actualite/%s-%d', $actualite->getSlug(), $actualite->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.gw-article-content strong', 'Information importante');
        self::assertSelectorTextContains('.gw-article-content li', 'Premier point');
        self::assertSelectorNotExists('.gw-article-content script');

        $entityManager->remove($actualite);
        $entityManager->flush();
    }

    public function testLaNavigationSequentielleExposeLesLiensPrevNext(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));

        $precedente = (new Actualite())
            ->setTitre('Actualité précédente '.$suffixe)
            ->setSlug('actualite-precedente-'.$suffixe)
            ->setDescription('Article précédent.')
            ->setContenu('Contenu.')
            ->setCategorie(CategorieActualite::News)
            ->setStatut(StatutActualite::Publiee);
        $courante = (new Actualite())
            ->setTitre('Actualité courante '.$suffixe)
            ->setSlug('actualite-courante-'.$suffixe)
            ->setDescription('Article courant.')
            ->setContenu('Contenu.')
            ->setCategorie(CategorieActualite::News)
            ->setStatut(StatutActualite::Publiee);
        $suivante = (new Actualite())
            ->setTitre('Actualité suivante '.$suffixe)
            ->setSlug('actualite-suivante-'.$suffixe)
            ->setDescription('Article suivant.')
            ->setContenu('Contenu.')
            ->setCategorie(CategorieActualite::News)
            ->setStatut(StatutActualite::Publiee);

        $entityManager->persist($precedente);
        $entityManager->persist($courante);
        $entityManager->persist($suivante);
        $entityManager->flush();

        $crawler = $client->request('GET', sprintf('/actualite/%s-%d', $courante->getSlug(), $courante->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller~="navigation-sequentielle"]');
        self::assertCount(1, $crawler->filter('a[rel="prev"]'));
        self::assertCount(1, $crawler->filter('a[rel="next"]'));
        self::assertStringContainsString(
            sprintf('/actualite/%s-%d', $precedente->getSlug(), $precedente->getId()),
            (string) $crawler->filter('a[rel="prev"]')->attr('href')
        );
        self::assertStringContainsString(
            sprintf('/actualite/%s-%d', $suivante->getSlug(), $suivante->getId()),
            (string) $crawler->filter('a[rel="next"]')->attr('href')
        );

        $entityManager->remove($precedente);
        $entityManager->remove($courante);
        $entityManager->remove($suivante);
        $entityManager->flush();
    }

    public function testLaListeNExposeQueLesActualitesPubliees(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $publiee = (new Actualite())->setTitre('Actualité publique '.$suffixe)->setSlug('actualite-publique-'.$suffixe)->setDescription('Visible dans la liste.')->setCategorie(CategorieActualite::News)->setStatut(StatutActualite::Publiee);
        $brouillon = (new Actualite())->setTitre('Brouillon privé '.$suffixe)->setSlug('brouillon-prive-'.$suffixe)->setDescription('Invisible dans la liste.')->setCategorie(CategorieActualite::Mods)->setStatut(StatutActualite::Brouillon);
        $entityManager->persist($publiee);
        $entityManager->persist($brouillon);
        $entityManager->flush();

        $client->request('GET', '/actualites');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Actualité publique '.$suffixe);
        self::assertSelectorTextNotContains('body', 'Brouillon privé '.$suffixe);

        $entityManager->remove($publiee);
        $entityManager->remove($brouillon);
        $entityManager->flush();
    }

    public function testLaRechercheFiltreLesActualitesEnSql(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(5));
        $actualite = (new Actualite())->setTitre('Résultat '.$suffixe)->setSlug('recherche-'.$suffixe)->setDescription('Une actualité consacrée à Symfony.')->setContenu('Contenu.')->setCategorie(CategorieActualite::Tutoriels)->setStatut(StatutActualite::Publiee);
        $brouillon = (new Actualite())->setTitre('Résultat privé')->setSlug('recherche-privee-'.$suffixe)->setDescription('Brouillon '.$suffixe)->setContenu('Contenu privé.')->setCategorie(CategorieActualite::Tutoriels)->setStatut(StatutActualite::Brouillon);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($actualite);
        $entityManager->persist($brouillon);
        $entityManager->flush();

        $client->request('GET', '/actualites?recherche='.$suffixe);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Résultat '.$suffixe);
        self::assertSelectorTextNotContains('body', 'Résultat privé');
        self::assertSelectorExists('input[name="recherche"][value="'.$suffixe.'"]');

        $entityManager->remove($actualite);
        $entityManager->remove($brouillon);
        $entityManager->flush();
    }

    public function testLaRouteGlitchsEstAccessibleDepuisLaNavigation(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $glitch = (new Actualite())
            ->setTitre('Glitch public '.$suffixe)
            ->setSlug('glitch-public-'.$suffixe)
            ->setDescription('Un glitch visible dans la section dédiée.')
            ->setCategorie(CategorieActualite::Glitchs)
            ->setStatut(StatutActualite::Publiee);
        $news = (new Actualite())
            ->setTitre('News publique '.$suffixe)
            ->setSlug('news-publique-'.$suffixe)
            ->setDescription('Une news hors section glitchs.')
            ->setCategorie(CategorieActualite::News)
            ->setStatut(StatutActualite::Publiee);

        $entityManager->persist($glitch);
        $entityManager->persist($news);
        $entityManager->flush();

        $client->request('GET', '/actualites/glitchs');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Glitchs');
        self::assertSelectorTextContains('body', 'Glitch public '.$suffixe);
        self::assertSelectorTextNotContains('body', 'News publique '.$suffixe);
        self::assertSelectorExists('a.gw-rail__item[href="/actualites/glitchs"]');

        $entityManager->remove($glitch);
        $entityManager->remove($news);
        $entityManager->flush();
    }
}
