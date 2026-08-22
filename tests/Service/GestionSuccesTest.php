<?php

namespace App\Tests\Service;

use App\Entity\ListeJeux;
use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GestionSuccesTest extends KernelTestCase
{
    public function testDebloqueLesSuccesDeProfilEtDeListe(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $gestion = self::getContainer()->get(GestionSucces::class);

        $suffixe = bin2hex(random_bytes(5));
        $utilisateur = (new Utilisateur())
            ->setPseudo('Succes'.$suffixe)
            ->setEmail('succes-'.$suffixe.'@test.local')
            ->setAvatar('avatar.jpg')
            ->setBanniere('banniere.jpg')
            ->setBiographie('Une présentation pour le test.');
        $liste = (new ListeJeux())->setUtilisateur($utilisateur)->setNom('À rejouer');
        $entityManager->persist($utilisateur);
        $entityManager->persist($liste);
        $entityManager->flush();
        $utilisateurId = $utilisateur->getId();

        $debloques = $gestion->verifier($utilisateur, false);
        $codes = array_map(static fn (SuccesUtilisateur $deblocage) => $deblocage->getSucces()?->getCode(), $debloques);

        self::assertContains('portrait', $codes);
        self::assertContains('presentation', $codes);
        self::assertContains('premiere_banniere', $codes);
        self::assertContains('premiere_liste', $codes);

        $entityManager->clear();
        $utilisateur = $entityManager->find(Utilisateur::class, $utilisateurId);
        foreach ($entityManager->getRepository(SuccesUtilisateur::class)->findBy(['utilisateur' => $utilisateur]) as $deblocage) {
            $entityManager->remove($deblocage);
        }
        foreach ($entityManager->getRepository(ListeJeux::class)->findBy(['utilisateur' => $utilisateur]) as $listeUtilisateur) {
            $entityManager->remove($listeUtilisateur);
        }
        $entityManager->remove($utilisateur);
        $entityManager->flush();
    }
}
