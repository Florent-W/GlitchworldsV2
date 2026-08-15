<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Index(columns: ['publiee_le'], name: 'publication_date_idx')]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $auteur = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Écris un message avant de publier.', normalizer: 'trim')]
    #[Assert\Length(min: 3, max: 1000, normalizer: 'trim')]
    private string $contenu = '';

    #[ORM\Column]
    private \DateTimeImmutable $publieeLe;

    public function __construct()
    {
        $this->publieeLe = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAuteur(): ?Utilisateur { return $this->auteur; }
    public function setAuteur(?Utilisateur $auteur): static { $this->auteur = $auteur; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = trim($contenu); return $this; }
    public function getPublieeLe(): \DateTimeImmutable { return $this->publieeLe; }
}
