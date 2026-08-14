<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
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

    #[ORM\Column(nullable: true)]
    private ?string $motDePasse = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    /** @var Collection<int, Jeu> */
    #[ORM\ManyToMany(targetEntity: Jeu::class, inversedBy: 'ajouteAuxFavorisPar')]
    #[ORM\JoinTable(name: 'utilisateur_jeu_favori')]
    private Collection $jeuxFavoris;

    public function __construct()
    {
        $this->jeuxFavoris = new ArrayCollection();
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

    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
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
