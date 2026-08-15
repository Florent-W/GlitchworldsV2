<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $auteur = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(min: 1, max: 2000, normalizer: 'trim')]
    private string $contenu = '';

    #[ORM\Column]
    private \DateTimeImmutable $envoyeLe;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $luLe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pieceJointe = null;

    public function __construct() { $this->envoyeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getConversation(): ?Conversation { return $this->conversation; }
    public function setConversation(Conversation $conversation): static { $this->conversation = $conversation; return $this; }
    public function getAuteur(): ?Utilisateur { return $this->auteur; }
    public function setAuteur(?Utilisateur $auteur): static { $this->auteur = $auteur; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = trim($contenu); return $this; }
    public function getEnvoyeLe(): \DateTimeImmutable { return $this->envoyeLe; }
    public function getLuLe(): ?\DateTimeImmutable { return $this->luLe; }
    public function marquerCommeLu(): void { $this->luLe ??= new \DateTimeImmutable(); }
    public function getPieceJointe(): ?string { return $this->pieceJointe; }
    public function setPieceJointe(?string $pieceJointe): static { $this->pieceJointe = $pieceJointe; return $this; }
}
