<?php
namespace App\Entity;
use App\Repository\IdentiteOauthRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: IdentiteOauthRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_OAUTH_FOURNISSEUR_IDENTIFIANT', columns: ['fournisseur', 'identifiant'])]
class IdentiteOauth
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column(length: 20)] private string $fournisseur = '';
    #[ORM\Column(length: 191)] private string $identifiant = '';
    #[ORM\Column(length: 180, nullable: true)] private ?string $email = null;
    #[ORM\Column] private \DateTimeImmutable $lieeLe;
    public function __construct() { $this->lieeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getFournisseur(): string { return $this->fournisseur; }
    public function setFournisseur(string $fournisseur): static { $this->fournisseur = $fournisseur; return $this; }
    public function getIdentifiant(): string { return $this->identifiant; }
    public function setIdentifiant(string $identifiant): static { $this->identifiant = $identifiant; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }
    public function getLieeLe(): \DateTimeImmutable { return $this->lieeLe; }
}
