<?php

namespace App\Service;

use App\Entity\MouvementProgression;
use App\Entity\Succes;
use App\Entity\SuccesUtilisateur;
use App\Entity\Utilisateur;
use App\Repository\MouvementProgressionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProgressionUtilisateur
{
    public const COMMENTAIRE_XP = 10;
    public const COMMENTAIRE_POINTS = 5;
    public const PUBLICATION_XP = 20;
    public const PUBLICATION_POINTS = 10;
    public const JEU_APPROUVE_XP = 100;
    public const JEU_APPROUVE_POINTS = 50;

    public function __construct(private EntityManagerInterface $entityManager, private MouvementProgressionRepository $mouvements) {}

    public function recompenseCommentaire(Utilisateur $utilisateur, string $source): bool { return $this->crediter($utilisateur, 'commentaire', 'commentaire:'.$source, 'Commentaire publié', self::COMMENTAIRE_XP, self::COMMENTAIRE_POINTS, 10); }

    public function recompensePublication(Utilisateur $utilisateur, string $source): bool { return $this->crediter($utilisateur, 'publication', 'publication:'.$source, 'Publication communautaire', self::PUBLICATION_XP, self::PUBLICATION_POINTS, 3); }

    public function recompenseJeuApprouve(Utilisateur $utilisateur, int $jeuId): bool { return $this->crediter($utilisateur, 'jeu_approuve', 'jeu-approuve:'.$jeuId, 'Jeu approuvé', self::JEU_APPROUVE_XP, self::JEU_APPROUVE_POINTS); }
    public function recompenseNote(Utilisateur $utilisateur, int $jeuId): bool { return $this->crediter($utilisateur, 'note', 'note:'.$jeuId, 'Première note sur un jeu', 5, 2); }
    public function recompenseFavori(Utilisateur $utilisateur, int $jeuId): bool { return $this->crediter($utilisateur, 'favori', 'favori:'.$jeuId, 'Premier ajout d’un jeu aux favoris', 3, 1); }
    public function recompenseAnciennete(Utilisateur $utilisateur): bool { $annees = $utilisateur->getInscritLe()->diff(new \DateTimeImmutable())->y; return $annees > 0 && $this->crediter($utilisateur, 'anciennete', 'anciennete:'.$annees, $annees.' an'.($annees > 1 ? 's' : '').' d’ancienneté', 25, 10); }
    public function recompenseSucces(Utilisateur $utilisateur, Succes $succes): bool { return $this->crediter($utilisateur, 'succes', 'succes:'.$succes->getCode(), 'Succès : '.$succes->getNom(), 0, $succes->getPoints()); }
    public function debiterBoutique(Utilisateur $utilisateur, int $articleId, string $nom, int $points): void { if ($utilisateur->getPoints() < $points) { throw new \DomainException('Tu n’as pas assez de points pour cet achat.'); } $this->appliquer($utilisateur, 'boutique', $utilisateur->getId().':achat:'.$articleId, 'Achat : '.$nom, 0, -$points); }

    private function crediter(Utilisateur $utilisateur, string $categorie, string $cle, string $libelle, int $experience, int $points, ?int $plafondJournalier = null): bool
    {
        $cle = $utilisateur->getId().':'.$cle;
        if ($this->mouvements->existe($utilisateur, $cle)) { return false; }
        if ($plafondJournalier !== null && $this->mouvements->compterDepuis($utilisateur, $categorie, new \DateTimeImmutable('today')) >= $plafondJournalier) { return false; }
        $this->appliquer($utilisateur, $categorie, $cle, $libelle, $experience, $points);
        $this->debloquerBadgesNiveau($utilisateur);
        return true;
    }

    private function appliquer(Utilisateur $utilisateur, string $categorie, string $cle, string $libelle, int $experience, int $points): void { $utilisateur->setExperience($utilisateur->getExperience() + $experience)->setPoints($utilisateur->getPoints() + $points); $this->entityManager->persist(new MouvementProgression($utilisateur, $categorie, $cle, $libelle, $experience, $points)); }

    private function debloquerBadgesNiveau(Utilisateur $utilisateur): void
    {
        foreach ([5, 10, 20, 50] as $niveau) {
            if ($utilisateur->getNiveau() < $niveau) { continue; }
            $succes = $this->entityManager->getRepository(Succes::class)->findOneBy(['code' => 'niveau_'.$niveau]);
            if (!$succes || $this->entityManager->getRepository(SuccesUtilisateur::class)->findOneBy(['utilisateur' => $utilisateur, 'succes' => $succes])) { continue; }
            $this->entityManager->persist((new SuccesUtilisateur())->setUtilisateur($utilisateur)->setSucces($succes));
        }
    }
}
