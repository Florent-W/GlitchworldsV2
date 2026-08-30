<?php

namespace App\Entity;

use App\Repository\CommentaireActualiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireActualiteRepository::class)]
class CommentaireActualite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Actualite $actualite = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $auteur = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Écris un commentaire avant de publier.', normalizer: 'trim')]
    #[Assert\Length(min: 3, max: 1000, normalizer: 'trim')]
    private string $contenu = '';

    #[ORM\Column]
    private \DateTimeImmutable $dateCommentaire;

    /** @var Collection<int, Utilisateur> */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    #[ORM\JoinTable(name: 'commentaire_actualite_aime')]
    private Collection $aimePar;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['dateCommentaire' => 'ASC'])]
    private Collection $reponses;

    public function __construct() { $this->dateCommentaire = new \DateTimeImmutable(); $this->aimePar = new ArrayCollection(); $this->reponses = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getActualite(): ?Actualite { return $this->actualite; }
    public function setActualite(Actualite $actualite): static { $this->actualite = $actualite; return $this; }
    public function getAuteur(): ?Utilisateur { return $this->auteur; }
    public function setAuteur(?Utilisateur $auteur): static { $this->auteur = $auteur; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = trim($contenu); return $this; }
    public function getDateCommentaire(): \DateTimeImmutable { return $this->dateCommentaire; }
    public function setDateCommentaire(\DateTimeImmutable $dateCommentaire): static { $this->dateCommentaire = $dateCommentaire; return $this; }
    /** @return Collection<int, Utilisateur> */
    public function getAimePar(): Collection { return $this->aimePar; }
    public function ajouterAime(Utilisateur $utilisateur): static { if (!$this->aimePar->contains($utilisateur)) { $this->aimePar->add($utilisateur); } return $this; }
    public function retirerAime(Utilisateur $utilisateur): static { $this->aimePar->removeElement($utilisateur); return $this; }
    public function estAimePar(Utilisateur $utilisateur): bool { return $this->aimePar->contains($utilisateur); }
    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }
    /** @return Collection<int, self> */
    public function getReponses(): Collection { return $this->reponses; }
}
