<?php

namespace App\Entity;

use App\Enum\StatutJeu;
use App\Repository\JeuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JeuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Jeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 160)]
    private string $description = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $contenu = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateSortie = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $developpeur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?CategorieJeu $categorie = null;

    /**
     * @var Collection<int, Plateforme>
     */
    #[ORM\ManyToMany(targetEntity: Plateforme::class)]
    #[ORM\JoinTable(name: 'jeu_plateforme')]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $plateformes;

    /**
     * @var Collection<int, Genre>
     */
    #[ORM\ManyToMany(targetEntity: Genre::class)]
    #[ORM\JoinTable(name: 'jeu_genre')]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $genres;

    #[ORM\Column(enumType: StatutJeu::class)]
    private StatutJeu $statut = StatutJeu::Brouillon;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $miniature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    #[ORM\Column]
    private \DateTimeImmutable $modifieLe;

    public function __construct()
    {
        $this->plateformes = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->creeLe = new \DateTimeImmutable();
        $this->modifieLe = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getDateSortie(): ?\DateTimeImmutable
    {
        return $this->dateSortie;
    }

    public function setDateSortie(?\DateTimeImmutable $dateSortie): static
    {
        $this->dateSortie = $dateSortie;

        return $this;
    }

    public function getDeveloppeur(): ?string
    {
        return $this->developpeur;
    }

    public function setDeveloppeur(?string $developpeur): static
    {
        $this->developpeur = $developpeur;

        return $this;
    }

    public function getCategorie(): ?CategorieJeu
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieJeu $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, Plateforme>
     */
    public function getPlateformes(): Collection
    {
        return $this->plateformes;
    }

    public function addPlateforme(Plateforme $plateforme): static
    {
        if (!$this->plateformes->contains($plateforme)) {
            $this->plateformes->add($plateforme);
        }

        return $this;
    }

    public function removePlateforme(Plateforme $plateforme): static
    {
        $this->plateformes->removeElement($plateforme);

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    public function getStatut(): StatutJeu
    {
        return $this->statut;
    }

    public function setStatut(StatutJeu $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMiniature(): ?string
    {
        return $this->miniature;
    }

    public function setMiniature(?string $miniature): static
    {
        $this->miniature = $miniature;

        return $this;
    }

    public function getBanniere(): ?string
    {
        return $this->banniere;
    }

    public function setBanniere(?string $banniere): static
    {
        $this->banniere = $banniere;

        return $this;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }

    public function getModifieLe(): \DateTimeImmutable
    {
        return $this->modifieLe;
    }
}
