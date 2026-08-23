<?php

namespace App\Entity;

use App\Enum\StatutJeu;
use App\Repository\JeuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JeuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Jeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255, normalizer: 'trim')]
    private ?string $nom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 160)]
    #[Assert\Length(max: 160, normalizer: 'trim')]
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

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $createur = null;

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

    /**
     * @var Collection<int, Langue>
     */
    #[ORM\ManyToMany(targetEntity: Langue::class)]
    #[ORM\JoinTable(name: 'jeu_langue')]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $langues;

    /** @var Collection<int, Utilisateur> */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class, mappedBy: 'jeuxFavoris')]
    private Collection $ajouteAuxFavorisPar;

    /** @var Collection<int, Actualite> */
    #[ORM\ManyToMany(targetEntity: Actualite::class, mappedBy: 'jeux')]
    private Collection $actualites;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'modsAssocies')]
    #[ORM\JoinTable(name: 'mod_jeu')]
    #[ORM\JoinColumn(name: 'mod_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'jeu_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $jeuxAssocies;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'jeuxAssocies')]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $modsAssocies;

    #[ORM\Column(enumType: StatutJeu::class)]
    private StatutJeu $statut = StatutJeu::Brouillon;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: 'L’URL de la vidéo de fond n’est pas valide.')]
    private ?string $videoBackground = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $miniature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $galerie = [];

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    #[ORM\Column]
    private \DateTimeImmutable $modifieLe;

    public function __construct()
    {
        $this->plateformes = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->langues = new ArrayCollection();
        $this->ajouteAuxFavorisPar = new ArrayCollection();
        $this->actualites = new ArrayCollection();
        $this->jeuxAssocies = new ArrayCollection();
        $this->modsAssocies = new ArrayCollection();
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

    /** @return Collection<int, Actualite> */
    public function getActualites(): Collection
    {
        return $this->actualites;
    }

    /** @return Collection<int, self> */
    public function getJeuxAssocies(): Collection { return $this->jeuxAssocies; }
    public function addJeuxAssocy(self $jeu): static { if ($jeu !== $this && !$this->jeuxAssocies->contains($jeu)) { $this->jeuxAssocies->add($jeu); } return $this; }
    public function removeJeuxAssocy(self $jeu): static { $this->jeuxAssocies->removeElement($jeu); return $this; }
    /** @return Collection<int, self> */
    public function getModsAssocies(): Collection { return $this->modsAssocies; }

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

    public function getCreateur(): ?Utilisateur
    {
        return $this->createur;
    }

    public function setCreateur(?Utilisateur $createur): static
    {
        $this->createur = $createur;

        return $this;
    }

    public function getPseudoAuteurFiche(): ?string
    {
        if ($this->createur instanceof Utilisateur) {
            return $this->createur->getPseudo();
        }

        $pseudoLegacy = trim((string) ($this->developpeur ?? ''));

        return $pseudoLegacy !== '' ? $pseudoLegacy : null;
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

    /**
     * @return Collection<int, Langue>
     */
    public function getLangues(): Collection
    {
        return $this->langues;
    }

    public function addLangue(Langue $langue): static
    {
        if (!$this->langues->contains($langue)) {
            $this->langues->add($langue);
        }

        return $this;
    }

    public function removeLangue(Langue $langue): static
    {
        $this->langues->removeElement($langue);

        return $this;
    }

    /** @return Collection<int, Utilisateur> */
    public function getAjouteAuxFavorisPar(): Collection
    {
        return $this->ajouteAuxFavorisPar;
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

    public function getVideoBackground(): ?string
    {
        return $this->videoBackground;
    }

    public function setVideoBackground(?string $videoBackground): static
    {
        $this->videoBackground = $videoBackground;

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

    /** @return list<string> */
    public function getGalerie(): array
    {
        return $this->galerie;
    }

    /** @param list<string> $galerie */
    public function setGalerie(array $galerie): static
    {
        $this->galerie = array_values(array_unique($galerie));

        return $this;
    }

    public function addImageGalerie(string $image): static
    {
        if (!in_array($image, $this->galerie, true)) {
            $this->galerie[] = $image;
        }

        return $this;
    }

    public function removeImageGalerie(string $image): static
    {
        $this->galerie = array_values(array_filter(
            $this->galerie,
            static fn (string $element): bool => $element !== $image,
        ));

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
