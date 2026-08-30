<?php

namespace App\Tests\Service;

use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Repository\MouvementProgressionRepository;
use App\Service\RetroProgressionLegacy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RetroProgressionLegacyTest extends KernelTestCase
{
    public function testCrediteLesActionsImporteesUneSeuleFois(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $retro = self::getContainer()->get(RetroProgressionLegacy::class);
        $mouvements = self::getContainer()->get(MouvementProgressionRepository::class);

        $suffixe = bin2hex(random_bytes(5));
        $utilisateur = (new Utilisateur())
            ->setPseudo('Retro'.$suffixe)
            ->setEmail(sprintf('retro-%s@glitchworlds.local', $suffixe))
            ->setInscritLe(new \DateTimeImmutable('-2 years'));
        $jeu = (new Jeu())
            ->setNom('Jeu rétro '.$suffixe)
            ->setSlug('jeu-retro-'.$suffixe)
            ->setDescription('Test rétro progression.')
            ->setStatut(StatutJeu::Approuve);
        $commentaire = (new CommentaireJeu())
            ->setAuteur($utilisateur)
            ->setJeu($jeu)
            ->setContenu('Commentaire legacy simulé.');

        $entityManager->persist($utilisateur);
        $entityManager->persist($jeu);
        $entityManager->persist($commentaire);
        $entityManager->flush();

        $utilisateurId = $utilisateur->getId();
        $commentaireId = $commentaire->getId();

        $stats = $retro->attribuer($utilisateurId, false, false);
        self::assertSame(1, $stats['commentaires_jeu']);
        self::assertGreaterThan(0, $stats['experience']);

        $entityManager->clear();
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        $experienceApresPremierPassage = $utilisateur->getExperience();

        $stats = $retro->attribuer($utilisateurId, false, false);
        self::assertSame(0, $stats['commentaires_jeu']);

        $entityManager->clear();
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        self::assertSame($experienceApresPremierPassage, $utilisateur->getExperience());
        self::assertTrue($mouvements->existe($utilisateur, $utilisateurId.':legacy:commentaire-jeu:'.$commentaireId));

        $entityManager->remove($entityManager->find(CommentaireJeu::class, $commentaireId));
        $entityManager->remove($entityManager->find(Jeu::class, $jeu->getId()));
        $entityManager->remove($entityManager->find(Utilisateur::class, $utilisateurId));
        $entityManager->flush();
    }
}
