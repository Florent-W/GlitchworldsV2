<?php

namespace App\Service;

use App\Entity\MouvementProgression;
use App\Entity\Succes;
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
    public function recompenseSucces(Utilisateur $utilisateur, Succes $succes): bool
    {
        $points = $succes->getPoints();
        $experience = $points > 0 ? $points : 15;

        return $this->crediter($utilisateur, 'succes', 'succes:'.$succes->getCode(), 'Succès : '.$succes->getNom(), $experience, $points);
    }

    /** Crédite une action importée sans plafond journalier (clé préfixée legacy:). */
    public function crediterHistorique(Utilisateur $utilisateur, string $categorie, string $cle, string $libelle, int $experience, int $points): bool
    {
        return $this->crediter($utilisateur, $categorie, 'legacy:'.$cle, $libelle, $experience, $points, null);
    }
    public function debiterBoutique(Utilisateur $utilisateur, int $articleId, string $nom, int $points, int $numeroAchat = 1): void { if ($utilisateur->getPoints() < $points) { throw new \DomainException('Tu n’as pas assez de points pour cet achat.'); } $suffixe = $numeroAchat > 1 ? ':'.$numeroAchat : ''; $this->appliquer($utilisateur, 'boutique', $utilisateur->getId().':achat:'.$articleId.$suffixe, 'Achat : '.$nom.($numeroAchat > 1 ? ' (emplacement '.$numeroAchat.')' : ''), 0, -$points); }
    public function achatBoutiqueDejaDebite(Utilisateur $utilisateur, int $articleId, int $numeroAchat = 1): bool { $suffixe = $numeroAchat > 1 ? ':'.$numeroAchat : ''; return $this->mouvements->existe($utilisateur, $utilisateur->getId().':achat:'.$articleId.$suffixe); }

    private function crediter(Utilisateur $utilisateur, string $categorie, string $cle, string $libelle, int $experience, int $points, ?int $plafondJournalier = null): bool
    {
        $cle = $utilisateur->getId().':'.$cle;
        if ($this->mouvements->existe($utilisateur, $cle)) { return false; }
        if ($plafondJournalier !== null && $this->mouvements->compterDepuis($utilisateur, $categorie, new \DateTimeImmutable('today')) >= $plafondJournalier) { return false; }
        $this->appliquer($utilisateur, $categorie, $cle, $libelle, $experience, $points);

        return true;
    }

    private function appliquer(Utilisateur $utilisateur, string $categorie, string $cle, string $libelle, int $experience, int $points): void { $utilisateur->setExperience($utilisateur->getExperience() + $experience)->setPoints($utilisateur->getPoints() + $points); $this->entityManager->persist(new MouvementProgression($utilisateur, $categorie, $cle, $libelle, $experience, $points)); }
}
