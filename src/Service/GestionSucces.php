<?php
namespace App\Service;
use App\Entity\JeuBibliotheque;
use App\Entity\Succes;
use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
final readonly class GestionSucces
{
    public function __construct(private EntityManagerInterface $entityManager, private CentreNotifications $notifications, private ProgressionUtilisateur $progression) {}
    /** @return list<SuccesUtilisateur> */
    public function verifier(Utilisateur $utilisateur): array
    {
        $nombreJeux = $this->entityManager->getRepository(JeuBibliotheque::class)->count(['utilisateur' => $utilisateur]);
        $criteres = ['premier_jeu' => $nombreJeux >= 1, 'collectionneur_5' => $nombreJeux >= 5];
        foreach ([5, 10, 20, 50] as $niveau) { $criteres['niveau_'.$niveau] = $utilisateur->getNiveau() >= $niveau; }
        $debloques = [];
        foreach ($this->entityManager->getRepository(Succes::class)->findAll() as $succes) {
            if (!($criteres[$succes->getCode()] ?? false) || $this->entityManager->getRepository(SuccesUtilisateur::class)->findOneBy(['utilisateur' => $utilisateur, 'succes' => $succes])) { continue; }
            $deblocage = (new SuccesUtilisateur())->setUtilisateur($utilisateur)->setSucces($succes);
            $this->entityManager->persist($deblocage);
            $this->progression->recompenseSucces($utilisateur, $succes);
            $this->notifications->ajouter($utilisateur, 'Succès débloqué', $succes->getNom().' - +'.$succes->getPoints().' points', 'trophy-fill', '/mes-jeux#succes');
            $debloques[] = $deblocage;
        }
        $this->entityManager->flush();
        return $debloques;
    }
}
