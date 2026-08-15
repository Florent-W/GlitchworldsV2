<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\UniqueConstraint(name: 'conversation_membres_unique', columns: ['membre_a_id', 'membre_b_id'])]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $membreA = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $membreB = null;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'conversation', orphanRemoval: true)]
    #[ORM\OrderBy(['envoyeLe' => 'ASC'])]
    private Collection $messages;

    #[ORM\Column]
    private \DateTimeImmutable $miseAJourLe;

    #[ORM\Column]
    private bool $archiveeParA = false;

    #[ORM\Column]
    private bool $archiveeParB = false;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->miseAJourLe = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getMembreA(): ?Utilisateur { return $this->membreA; }
    public function setMembreA(Utilisateur $membre): static { $this->membreA = $membre; return $this; }
    public function getMembreB(): ?Utilisateur { return $this->membreB; }
    public function setMembreB(Utilisateur $membre): static { $this->membreB = $membre; return $this; }
    /** @return Collection<int, Message> */
    public function getMessages(): Collection { return $this->messages; }
    public function getMiseAJourLe(): \DateTimeImmutable { return $this->miseAJourLe; }
    public function actualiser(): void { $this->miseAJourLe = new \DateTimeImmutable(); }
    public function contient(Utilisateur $utilisateur): bool { return $this->membreA === $utilisateur || $this->membreB === $utilisateur; }
    public function autreMembre(Utilisateur $utilisateur): ?Utilisateur { return $this->membreA === $utilisateur ? $this->membreB : ($this->membreB === $utilisateur ? $this->membreA : null); }
    public function estArchiveePar(Utilisateur $utilisateur): bool { return $this->membreA === $utilisateur ? $this->archiveeParA : ($this->membreB === $utilisateur && $this->archiveeParB); }
    public function basculerArchive(Utilisateur $utilisateur): void { if ($this->membreA === $utilisateur) { $this->archiveeParA = !$this->archiveeParA; } elseif ($this->membreB === $utilisateur) { $this->archiveeParB = !$this->archiveeParB; } }
}
