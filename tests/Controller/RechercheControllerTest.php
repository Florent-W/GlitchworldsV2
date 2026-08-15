<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Enum\StatutActualite;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RechercheControllerTest extends WebTestCase
{
    public function testLaRechercheGlobaleNExposeQueLesContenusPublics(): void
    {
        $client = self::createClient();
        $suffixe = bin2hex(random_bytes(5));
        $jeu = (new Jeu())->setNom('Jeu '.$suffixe)->setSlug('jeu-'.$suffixe)->setDescription('Résultat public.')->setStatut(StatutJeu::Approuve);
        $jeuPrive = (new Jeu())->setNom('Jeu privé '.$suffixe)->setSlug('jeu-prive-'.$suffixe)->setDescription('Résultat privé.')->setStatut(StatutJeu::Brouillon);
        $actualite = (new Actualite())->setTitre('Actualité '.$suffixe)->setSlug('actualite-'.$suffixe)->setDescription('Résultat public.')->setContenu('Contenu.')->setStatut(StatutActualite::Publiee);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        foreach ([$jeu, $jeuPrive, $actualite] as $entite) { $entityManager->persist($entite); }
        $entityManager->flush();

        $client->request('GET', '/recherche?recherche='.$suffixe);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Jeu '.$suffixe);
        self::assertSelectorTextContains('body', 'Actualité '.$suffixe);
        self::assertSelectorTextNotContains('body', 'Jeu privé '.$suffixe);
        self::assertSelectorExists('meta[name="robots"][content="noindex,follow"]');

        foreach ([$jeu, $jeuPrive, $actualite] as $entite) { $entityManager->remove($entite); }
        $entityManager->flush();
    }
}
