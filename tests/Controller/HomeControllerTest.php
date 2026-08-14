<?php

namespace App\Tests\Controller;

use App\Entity\Avis;
use App\Entity\Jeu;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testAccueilAfficheUniquementLesJeuxApprouves(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $suffixe = bin2hex(random_bytes(5));
        $jeuApprouve = (new Jeu())
            ->setNom('Nouveauté approuvée')
            ->setSlug('nouveaute-approuvee-'.$suffixe)
            ->setDescription('Visible sur l’accueil.')
            ->setStatut(StatutJeu::Approuve);
        $jeuBrouillon = (new Jeu())
            ->setNom('Brouillon invisible')
            ->setSlug('brouillon-invisible-'.$suffixe)
            ->setDescription('Ne doit pas être affiché.')
            ->setStatut(StatutJeu::Brouillon);
        $avis = (new Avis())->setJeu($jeuApprouve)->setNote(5);
        $entityManager->persist($jeuApprouve);
        $entityManager->persist($jeuBrouillon);
        $entityManager->persist($avis);
        $entityManager->flush();
        $jeuApprouveId = $jeuApprouve->getId();
        $jeuBrouillonId = $jeuBrouillon->getId();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Nouveauté approuvée');
        self::assertSelectorTextNotContains('body', 'Brouillon invisible');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Jeu::class, $jeuApprouveId));
        $entityManager->remove($entityManager->find(Jeu::class, $jeuBrouillonId));
        $entityManager->flush();
    }
}
