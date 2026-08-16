<?php
namespace App\Entity;
use App\Repository\ReponsePublicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: ReponsePublicationRepository::class)]
class ReponsePublication
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'reponses'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Publication $publication = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?Utilisateur $auteur = null;
    #[ORM\Column(type: Types::TEXT)] #[Assert\NotBlank, Assert\Length(min: 2, max: 600)] private string $contenu = '';
    #[ORM\Column] private \DateTimeImmutable $publieeLe;
    public function __construct() { $this->publieeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(Publication $publication): static { $this->publication = $publication; return $this; }
    public function getAuteur(): ?Utilisateur { return $this->auteur; }
    public function setAuteur(?Utilisateur $auteur): static { $this->auteur = $auteur; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = trim($contenu); return $this; }
    public function getPublieeLe(): \DateTimeImmutable { return $this->publieeLe; }
}
