<?php

namespace App\Entity;

use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use App\Repository\ActualiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActualiteRepository::class)]
#[ORM\Index(columns: ['statut', 'publiee_le'], name: 'actualite_publique_idx')]
class Actualite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255, normalizer: 'trim')]
    private string $titre = '';

    #[ORM\Column(length: 180, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 160)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 160, normalizer: 'trim')]
    private string $description = '';

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(normalizer: 'trim')]
    private string $contenu = '';

    #[ORM\Column(enumType: CategorieActualite::class)]
    private CategorieActualite $categorie = CategorieActualite::News;

    #[ORM\Column(enumType: StatutActualite::class)]
    private StatutActualite $statut = StatutActualite::Brouillon;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $miniature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $auteur = null;

    /** @var Collection<int, Jeu> */
    #[ORM\ManyToMany(targetEntity: Jeu::class, inversedBy: 'actualites')]
    #[ORM\JoinTable(name: 'actualite_jeu')]
    private Collection $jeux;

    #[ORM\Column]
    private \DateTimeImmutable $publieeLe;

    #[ORM\Column(options: ['default' => false])]
    private bool $miseEnAvant = false;

    public function __construct()
    {
        $this->publieeLe = new \DateTimeImmutable();
        $this->jeux = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): static { $this->id = $id; return $this; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = trim($titre); return $this; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = trim($description); return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getCategorie(): CategorieActualite { return $this->categorie; }
    public function setCategorie(CategorieActualite $categorie): static { $this->categorie = $categorie; return $this; }
    public function getStatut(): StatutActualite { return $this->statut; }
    public function setStatut(StatutActualite $statut): static { $this->statut = $statut; return $this; }
    public function getMiniature(): ?string { return $this->miniature; }
    public function setMiniature(?string $miniature): static { $this->miniature = $miniature; return $this; }
    public function getBanniere(): ?string { return $this->banniere; }
    public function setBanniere(?string $banniere): static { $this->banniere = $banniere; return $this; }
    public function getAuteur(): ?Utilisateur { return $this->auteur; }
    public function setAuteur(?Utilisateur $auteur): static { $this->auteur = $auteur; return $this; }
    /** @return Collection<int, Jeu> */
    public function getJeux(): Collection { return $this->jeux; }
    public function ajouterJeu(Jeu $jeu): static { if (!$this->jeux->contains($jeu)) { $this->jeux->add($jeu); } return $this; }
    public function retirerJeu(Jeu $jeu): static { $this->jeux->removeElement($jeu); return $this; }
    public function getPublieeLe(): \DateTimeImmutable { return $this->publieeLe; }
    public function setPublieeLe(\DateTimeImmutable $publieeLe): static { $this->publieeLe = $publieeLe; return $this; }
    public function isMiseEnAvant(): bool { return $this->miseEnAvant; }
    public function setMiseEnAvant(bool $miseEnAvant): static { $this->miseEnAvant = $miseEnAvant; return $this; }
}
