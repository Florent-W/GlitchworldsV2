<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse e-mail est déjà utilisée.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $pseudo = '';

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    #[Assert\Email(message: 'Cette adresse e-mail n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $emailLegacy = null;

    #[ORM\Column(nullable: true)]
    private ?string $motDePasse = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $biographie = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $localisation = null;

    #[ORM\Column(length: 160, nullable: true)]
    #[Assert\Length(max: 160)]
    private ?string $statutProfil = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column]
    private \DateTimeImmutable $inscritLe;

    /** @var Collection<int, Jeu> */
    #[ORM\ManyToMany(targetEntity: Jeu::class, inversedBy: 'ajouteAuxFavorisPar')]
    #[ORM\JoinTable(name: 'utilisateur_jeu_favori')]
    private Collection $jeuxFavoris;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'abonnes')]
    #[ORM\JoinTable(name: 'utilisateur_abonnement')]
    #[ORM\JoinColumn(name: 'abonne_id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'suivi_id', onDelete: 'CASCADE')]
    private Collection $abonnements;

    /** @var Collection<int, self> */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'abonnements')]
    private Collection $abonnes;

    public function __construct()
    {
        $this->jeuxFavoris = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
        $this->abonnes = new ArrayCollection();
        $this->inscritLe = new \DateTimeImmutable();
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

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = trim($pseudo);

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email === null ? null : strtolower(trim($email));

        return $this;
    }

    public function getEmailLegacy(): ?string
    {
        return $this->emailLegacy;
    }

    public function getUserIdentifier(): string
    {
        return $this->email ?? $this->pseudo;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    public function setPassword(?string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBanniere(): ?string { return $this->banniere; }
    public function setBanniere(?string $banniere): static { $this->banniere = $banniere; return $this; }
    public function getBiographie(): ?string { return $this->biographie; }
    public function setBiographie(?string $biographie): static { $this->biographie = $biographie === null ? null : trim($biographie); return $this; }
    public function getLocalisation(): ?string { return $this->localisation; }
    public function setLocalisation(?string $localisation): static { $this->localisation = $localisation === null ? null : trim($localisation); return $this; }
    public function getStatutProfil(): ?string { return $this->statutProfil; }
    public function setStatutProfil(?string $statutProfil): static { $this->statutProfil = $statutProfil === null ? null : trim($statutProfil); return $this; }
    public function getDateNaissance(): ?\DateTimeImmutable { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static { $this->dateNaissance = $dateNaissance; return $this; }
    public function getInscritLe(): \DateTimeImmutable { return $this->inscritLe; }
    public function setInscritLe(\DateTimeImmutable $inscritLe): static { $this->inscritLe = $inscritLe; return $this; }

    /** @return Collection<int, self> */
    public function getAbonnements(): Collection { return $this->abonnements; }
    /** @return Collection<int, self> */
    public function getAbonnes(): Collection { return $this->abonnes; }
    public function suivre(self $utilisateur): static { if ($utilisateur !== $this && !$this->abonnements->contains($utilisateur)) { $this->abonnements->add($utilisateur); } return $this; }
    public function nePlusSuivre(self $utilisateur): static { $this->abonnements->removeElement($utilisateur); return $this; }
    public function suit(self $utilisateur): bool { return $this->abonnements->contains($utilisateur); }

    /** @return Collection<int, Jeu> */
    public function getJeuxFavoris(): Collection
    {
        return $this->jeuxFavoris;
    }

    public function ajouterJeuFavori(Jeu $jeu): static
    {
        if (!$this->jeuxFavoris->contains($jeu)) {
            $this->jeuxFavoris->add($jeu);
        }

        return $this;
    }

    public function retirerJeuFavori(Jeu $jeu): static
    {
        $this->jeuxFavoris->removeElement($jeu);

        return $this;
    }

    public function aPourFavori(Jeu $jeu): bool
    {
        return $this->jeuxFavoris->contains($jeu);
    }
}
