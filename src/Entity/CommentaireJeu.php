<?php

namespace App\Entity;

use App\Repository\CommentaireJeuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireJeuRepository::class)]
class CommentaireJeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Jeu $jeu = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $auteur = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Écris un commentaire avant de publier.')]
    #[Assert\Length(
        min: 3,
        max: 1000,
        minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.',
    )]
    private string $contenu = '';

    #[ORM\Column]
    private \DateTimeImmutable $dateCommentaire;

    public function __construct()
    {
        $this->dateCommentaire = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJeu(): ?Jeu
    {
        return $this->jeu;
    }

    public function setJeu(Jeu $jeu): static
    {
        $this->jeu = $jeu;

        return $this;
    }

    public function getAuteur(): ?Utilisateur
    {
        return $this->auteur;
    }

    public function setAuteur(?Utilisateur $auteur): static
    {
        $this->auteur = $auteur;

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

    public function getDateCommentaire(): \DateTimeImmutable
    {
        return $this->dateCommentaire;
    }

    public function setDateCommentaire(\DateTimeImmutable $dateCommentaire): static
    {
        $this->dateCommentaire = $dateCommentaire;

        return $this;
    }
}
