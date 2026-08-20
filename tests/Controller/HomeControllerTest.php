<?php

namespace App\Tests\Controller;

use App\Entity\Avis;
use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
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
        $auteur = (new Utilisateur())
            ->setPseudo('HomeAuteur'.$suffixe)
            ->setEmail(sprintf('home-auteur-%s@glitchworlds.local', $suffixe));
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
        $commentaire = (new CommentaireJeu())
            ->setJeu($jeuApprouve)
            ->setAuteur($auteur)
            ->setContenu('Commentaire visible sur l’accueil.');
        $entityManager->persist($auteur);
        $entityManager->persist($jeuApprouve);
        $entityManager->persist($jeuBrouillon);
        $entityManager->persist($avis);
        $entityManager->persist($commentaire);
        $entityManager->flush();
        $auteurId = $auteur->getId();
        $jeuApprouveId = $jeuApprouve->getId();
        $jeuBrouillonId = $jeuBrouillon->getId();

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Nouveauté approuvée');
        self::assertSelectorTextNotContains('body', 'Brouillon invisible');
        self::assertSelectorExists('.gw-note-badge');
        self::assertSelectorTextContains('#activite-communaute-title', 'Activité de la communauté');
        self::assertSelectorTextContains('.gw-activity', 'Commentaire visible sur l’accueil.');
        self::assertSelectorTextContains('.gw-members', 'HomeAuteur'.$suffixe);
        self::assertSelectorExists('[data-controller="disposition"]');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->remove($entityManager->find(Jeu::class, $jeuApprouveId));
        $entityManager->remove($entityManager->find(Jeu::class, $jeuBrouillonId));
        $entityManager->remove($entityManager->find(Utilisateur::class, $auteurId));
        $entityManager->flush();
    }
}
