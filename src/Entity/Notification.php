<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column(length: 120)] private string $titre = '';
    #[ORM\Column(length: 255)] private string $message = '';
    #[ORM\Column(length: 40)] private string $icone = 'bell-fill';
    #[ORM\Column(length: 255, nullable: true)] private ?string $url = null;
    #[ORM\Column] private bool $lue = false;
    #[ORM\Column] private \DateTimeImmutable $creeeLe;
    public function __construct() { $this->creeeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }
    public function getIcone(): string { return $this->icone; }
    public function setIcone(string $icone): static { $this->icone = $icone; return $this; }
    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }
    public function isLue(): bool { return $this->lue; }
    public function marquerLue(): static { $this->lue = true; return $this; }
    public function getCreeeLe(): \DateTimeImmutable { return $this->creeeLe; }
}
