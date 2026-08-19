<?php

namespace App\Service;

use App\Entity\Avis;
use App\Entity\CommentaireActualite;
use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\JeuBibliotheque;
use App\Entity\Publication;
use App\Entity\Succes;
use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;

final readonly class GestionSucces
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CentreNotifications $notifications,
        private ProgressionUtilisateur $progression,
    ) {
    }

    /**
     * Évalue tous les critères et débloque les succès manquants.
     *
     * @return list<SuccesUtilisateur>
     */
    public function verifier(Utilisateur $utilisateur): array
    {
        $criteres = $this->evaluerCriteres($utilisateur);
        $debloques = [];

        foreach ($this->entityManager->getRepository(Succes::class)->findAll() as $succes) {
            if (!($criteres[$succes->getCode()] ?? false)) {
                continue;
            }
            if ($this->entityManager->getRepository(SuccesUtilisateur::class)->findOneBy([
                'utilisateur' => $utilisateur,
                'succes' => $succes,
            ])) {
                continue;
            }

            $deblocage = (new SuccesUtilisateur())
                ->setUtilisateur($utilisateur)
                ->setSucces($succes);
            $this->entityManager->persist($deblocage);
            $this->progression->recompenseSucces($utilisateur, $succes);
            $this->notifications->ajouter(
                $utilisateur,
                'Succès débloqué',
                $succes->getNom().' — +'.$succes->getPoints().' points',
                'trophy-fill',
                '/mes-jeux#succes',
            );
            $debloques[] = $deblocage;
        }

        if ($debloques !== []) {
            $this->entityManager->flush();
        }

        return $debloques;
    }

    /** @return array<string, bool> */
    private function evaluerCriteres(Utilisateur $utilisateur): array
    {
        $nombreJeux = $this->entityManager->getRepository(JeuBibliotheque::class)->count(['utilisateur' => $utilisateur]);
        $nombreFavoris = $utilisateur->getJeuxFavoris()->count();
        $nombreNotes = $this->entityManager->getRepository(Avis::class)->count(['auteur' => $utilisateur]);
        $nombreCommentairesJeux = $this->entityManager->getRepository(CommentaireJeu::class)->count(['auteur' => $utilisateur]);
        $nombreCommentairesActualites = $this->entityManager->getRepository(CommentaireActualite::class)->count(['auteur' => $utilisateur]);
        $nombreCommentaires = $nombreCommentairesJeux + $nombreCommentairesActualites;
        $nombrePublications = $this->entityManager->getRepository(Publication::class)->count(['auteur' => $utilisateur]);
        $nombreAbonnements = $utilisateur->getAbonnements()->count();
        $nombreJeuxApprouves = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(j.id)')
            ->from(Jeu::class, 'j')
            ->andWhere('j.createur = :utilisateur')
            ->andWhere('j.statut = :statut')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', StatutJeu::Approuve)
            ->getQuery()
            ->getSingleScalarResult();

        $criteres = [
            'premier_jeu' => $nombreJeux >= 1,
            'collectionneur_5' => $nombreJeux >= 5,
            'collectionneur_20' => $nombreJeux >= 20,
            'premier_favori' => $nombreFavoris >= 1,
            'fan_10' => $nombreFavoris >= 10,
            'premiere_note' => $nombreNotes >= 1,
            'critique_5' => $nombreNotes >= 5,
            'premier_commentaire' => $nombreCommentaires >= 1,
            'bavard_25' => $nombreCommentaires >= 25,
            'premiere_publication' => $nombrePublications >= 1,
            'voix_de_la_communaute' => $nombrePublications >= 10,
            'premier_suivi' => $nombreAbonnements >= 1,
            'createur_approuve' => $nombreJeuxApprouves >= 1,
        ];

        foreach ([5, 10, 20, 50] as $niveau) {
            $criteres['niveau_'.$niveau] = $utilisateur->getNiveau() >= $niveau;
        }

        return $criteres;
    }
}
