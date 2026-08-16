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
    public function getCouleur(): string { return $this->couleur; }
    public function setCouleur(string $couleur): static { $this->couleur = $couleur; return $this; }
    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): static { $this->points = $points; return $this; }
}
