<?php

namespace App\Entity;

use App\Enum\TypeArticleBoutique;
use App\Repository\ArticleBoutiqueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleBoutiqueRepository::class)]
class ArticleBoutique
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 100)] private string $nom = '';
    #[ORM\Column(length: 100, unique: true)] private string $slug = '';
    #[ORM\Column(length: 255)] private string $description = '';
    #[ORM\Column] private int $prix = 0;
    #[ORM\Column(enumType: TypeArticleBoutique::class)] private TypeArticleBoutique $type = TypeArticleBoutique::Badge;
    #[ORM\Column(length: 60)] private string $icone = 'stars';
    #[ORM\Column(length: 20)] private string $couleur = 'primary';
    #[ORM\Column(options: ['default' => true])] private bool $actif = true;
    #[ORM\Column(nullable: true)] private ?int $stock = null;

    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getPrix(): int { return $this->prix; }
    public function setPrix(int $prix): static { $this->prix = max(0, $prix); return $this; }
    public function getType(): TypeArticleBoutique { return $this->type; }
    public function setType(TypeArticleBoutique $type): static { $this->type = $type; return $this; }
    public function getIcone(): string { return $this->icone; }
    public function setIcone(string $icone): static { $this->icone = $icone; return $this; }
    public function getCouleur(): string { return $this->couleur; }
    public function setCouleur(string $couleur): static { $this->couleur = $couleur; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }
    public function getStock(): ?int { return $this->stock; }
    public function setStock(?int $stock): static { $this->stock = $stock === null ? null : max(0, $stock); return $this; }
    public function retirerDuStock(): void { if ($this->stock !== null) { --$this->stock; } }
    public function isDisponible(): bool { return $this->actif && ($this->stock === null || $this->stock > 0); }
}
