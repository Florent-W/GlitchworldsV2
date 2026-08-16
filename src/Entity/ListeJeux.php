<?php

namespace App\Entity;

use App\Repository\ListeJeuxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ListeJeuxRepository::class)]
class ListeJeux
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column(length: 80)] #[Assert\NotBlank, Assert\Length(max: 80)] private string $nom = '';
    #[ORM\Column(length: 255, nullable: true)] private ?string $description = null;
    #[ORM\ManyToMany(targetEntity: Jeu::class)] #[ORM\JoinTable(name: 'liste_jeux_element')] private Collection $jeux;
    #[ORM\Column] private \DateTimeImmutable $creeLe;

    public function __construct() { $this->jeux = new ArrayCollection(); $this->creeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = trim($nom); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description === null ? null : trim($description); return $this; }
    /** @return Collection<int, Jeu> */ public function getJeux(): Collection { return $this->jeux; }
    public function ajouterJeu(Jeu $jeu): static { if (!$this->jeux->contains($jeu)) { $this->jeux->add($jeu); } return $this; }
    public function retirerJeu(Jeu $jeu): static { $this->jeux->removeElement($jeu); return $this; }
    public function contient(Jeu $jeu): bool { return $this->jeux->contains($jeu); }
    public function getCreeLe(): \DateTimeImmutable { return $this->creeLe; }
}
