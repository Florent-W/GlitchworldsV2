<?php

namespace App\Entity;

use App\Repository\SuccesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuccesRepository::class)]
class Succes
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 80, unique: true)] private string $code = '';
    #[ORM\Column(length: 100)] private string $nom = '';
    #[ORM\Column(length: 255)] private string $description = '';
    #[ORM\Column(length: 40)] private string $icone = 'trophy-fill';
    #[ORM\Column(length: 20)] private string $couleur = 'warning';
    #[ORM\Column] private int $points = 0;
    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getIcone(): string { return $this->icone; }
    public function setIcone(string $icone): static { $this->icone = $icone; return $this; }

    /** Palier visuel, le même que les récompenses : plus les points sont élevés, plus c’est rare. */
    public function getPalier(): string
    {
        return match (true) {
            $this->points < 50 => 'commun',
            $this->points < 100 => 'rare',
            $this->points < 200 => 'epique',
            $this->points < 300 => 'mythique',
            default => 'legendaire',
        };
    }

    public function getPalierLabel(): string
    {
        return match ($this->getPalier()) {
            'commun' => 'Commun',
            'rare' => 'Rare',
            'epique' => 'Épique',
            'mythique' => 'Mythique',
            default => 'Légendaire',
        };
    }

    /** Famille d’actions, pour regrouper les succès comme les récompenses. */
    public function getCategorie(): string
    {
        return match ($this->code) {
            'premier_jeu', 'collectionneur_5', 'collectionneur_20', 'collectionneur_50',
            'premier_favori', 'fan_10', 'fan_25',
            'premiere_liste', 'curateur_5' => 'collection',
            'premiere_note', 'critique_5', 'critique_15',
            'premier_commentaire', 'bavard_25', 'bavard_50',
            'premiere_publication', 'voix_de_la_communaute', 'chroniqueur_25',
            'premier_suivi', 'social_10',
            'premier_message' => 'communaute',
            'createur_approuve', 'createur_5', 'premiere_actualite' => 'creation',
            'portrait', 'presentation', 'premiere_banniere' => 'profil',
            'niveau_5', 'niveau_10', 'niveau_20', 'niveau_50' => 'progression',
            'premier_achat' => 'boutique',
            default => 'collection',
        };
    }

    public function getCouleur(): string
    {
        return match ($this->getPalier()) {
            'commun' => 'info',
            'rare' => 'success',
            'epique' => 'warning',
            'mythique' => 'secondary',
            default => 'danger',
        };
    }

    public function setCouleur(string $couleur): static { $this->couleur = $couleur; return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = $points; return $this; }
}
