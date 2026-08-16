<?php
namespace App\Entity;
use App\Repository\VotePublicationRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: VotePublicationRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_VOTE_PUBLICATION_MEMBRE', columns: ['publication_id', 'utilisateur_id'])]
class VotePublication
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'votes'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Publication $publication = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column] private int $optionChoisie = 0;
    public function getId(): ?int { return $this->id; }
    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(Publication $publication): static { $this->publication = $publication; return $this; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getOptionChoisie(): int { return $this->optionChoisie; }
    public function setOptionChoisie(int $option): static { $this->optionChoisie = $option; return $this; }
}
