<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ActualiteControllerTest extends WebTestCase
{
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
}
