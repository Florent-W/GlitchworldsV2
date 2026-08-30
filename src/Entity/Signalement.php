<?php

namespace App\Entity;

use App\Enum\MotifSignalement;
use App\Enum\StatutSignalement;
use App\Repository\SignalementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
#[ORM\Index(columns: ['statut', 'signale_le'], name: 'signalement_moderation_idx')]
class Signalement
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $signalePar = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $traitePar = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Jeu $jeu = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CommentaireJeu $commentaireJeu = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CommentaireActualite $commentaireActualite = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Publication $publication = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Utilisateur $profil = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Avis $avis = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Message $message = null;

    #[ORM\Column(enumType: MotifSignalement::class)]
    private MotifSignalement $motif = MotifSignalement::Autre;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $details = null;

    #[ORM\Column(enumType: StatutSignalement::class)]
    private StatutSignalement $statut = StatutSignalement::EnAttente;

    #[ORM\Column]
    private \DateTimeImmutable $signaleLe;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $traiteLe = null;

    public function __construct() { $this->signaleLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getSignalePar(): ?Utilisateur { return $this->signalePar; }
    public function setSignalePar(?Utilisateur $utilisateur): static { $this->signalePar = $utilisateur; return $this; }
    public function getTraitePar(): ?Utilisateur { return $this->traitePar; }
    public function setTraitePar(?Utilisateur $utilisateur): static { $this->traitePar = $utilisateur; return $this; }
    public function getJeu(): ?Jeu { return $this->jeu; }
    public function setJeu(?Jeu $jeu): static { $this->jeu = $jeu; return $this; }
    public function getCommentaireJeu(): ?CommentaireJeu { return $this->commentaireJeu; }
    public function setCommentaireJeu(?CommentaireJeu $commentaire): static { $this->commentaireJeu = $commentaire; return $this; }
    public function getCommentaireActualite(): ?CommentaireActualite { return $this->commentaireActualite; }
    public function setCommentaireActualite(?CommentaireActualite $commentaire): static { $this->commentaireActualite = $commentaire; return $this; }
    public function getPublication(): ?Publication { return $this->publication; }
    public function setPublication(?Publication $publication): static { $this->publication = $publication; return $this; }
    public function getProfil(): ?Utilisateur { return $this->profil; }
    public function setProfil(?Utilisateur $profil): static { $this->profil = $profil; return $this; }
    public function getAvis(): ?Avis { return $this->avis; }
    public function setAvis(?Avis $avis): static { $this->avis = $avis; return $this; }
    public function getMessage(): ?Message { return $this->message; }
    public function setMessage(?Message $message): static { $this->message = $message; return $this; }
    public function getMotif(): MotifSignalement { return $this->motif; }
    public function setMotif(MotifSignalement $motif): static { $this->motif = $motif; return $this; }
    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $this->details = ($details = trim((string) $details)) !== '' ? $details : null; return $this; }
    public function getStatut(): StatutSignalement { return $this->statut; }
    public function setStatut(StatutSignalement $statut): static { $this->statut = $statut; return $this; }
    public function getSignaleLe(): \DateTimeImmutable { return $this->signaleLe; }
    public function getTraiteLe(): ?\DateTimeImmutable { return $this->traiteLe; }
    public function cloturer(StatutSignalement $statut, Utilisateur $moderateur): static { $this->statut = $statut; $this->traitePar = $moderateur; $this->traiteLe = new \DateTimeImmutable(); return $this; }
    public function getTypeCible(): string { return $this->jeu ? 'Jeu' : ($this->commentaireJeu ? 'Commentaire de jeu' : ($this->commentaireActualite ? 'Commentaire d’actualité' : ($this->publication ? 'Publication' : ($this->profil ? 'Profil' : ($this->avis ? 'Avis' : ($this->message ? 'Message privé' : 'Contenu supprimé')))))); }
    public function getExtraitCible(): string { return $this->jeu?->getNom() ?? $this->commentaireJeu?->getContenu() ?? $this->commentaireActualite?->getContenu() ?? $this->publication?->getContenu() ?? $this->profil?->getPseudo() ?? $this->avis?->getContenu() ?? $this->message?->getContenu() ?? 'Ce contenu n’existe plus.'; }
}
