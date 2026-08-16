<?php

namespace App\Entity;

use App\Repository\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url]
    private ?string $lien = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $questionSondage = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $optionsSondage = [];

    #[ORM\Column]
    private \DateTimeImmutable $publieeLe;

    /** @var Collection<int, Utilisateur> */
    #[ORM\ManyToMany(targetEntity: Utilisateur::class)]
    #[ORM\JoinTable(name: 'publication_aime')]
    private Collection $aimePar;

    /** @var Collection<int, ReponsePublication> */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: ReponsePublication::class, cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['publieeLe' => 'ASC'])]
    private Collection $reponses;

    /** @var Collection<int, VotePublication> */
    #[ORM\OneToMany(mappedBy: 'publication', targetEntity: VotePublication::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $votes;

    public function __construct()
    {
        $this->publieeLe = new \DateTimeImmutable();
        $this->aimePar = new ArrayCollection();
        $this->reponses = new ArrayCollection();
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getAuteur(): ?Utilisateur { return $this->auteur; }
    public function setAuteur(?Utilisateur $auteur): static { $this->auteur = $auteur; return $this; }
    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = trim($contenu); return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }
    public function getLien(): ?string { return $this->lien; }
    public function setLien(?string $lien): static { $this->lien = $lien === null || trim($lien) === '' ? null : trim($lien); return $this; }
    public function getQuestionSondage(): ?string { return $this->questionSondage; }
    public function setQuestionSondage(?string $question): static { $this->questionSondage = $question === null || trim($question) === '' ? null : trim($question); return $this; }
    /** @return list<string> */ public function getOptionsSondage(): array { return $this->optionsSondage; }
    /** @param list<string> $options */ public function setOptionsSondage(array $options): static { $this->optionsSondage = array_values(array_filter(array_map('trim', $options))); return $this; }
    public function isSondage(): bool { return $this->questionSondage !== null && count($this->optionsSondage) >= 2; }
    /** @return Collection<int, ReponsePublication> */ public function getReponses(): Collection { return $this->reponses; }
    /** @return Collection<int, VotePublication> */ public function getVotes(): Collection { return $this->votes; }
    public function nombreVotesPour(int $option): int { return $this->votes->filter(static fn (VotePublication $vote) => $vote->getOptionChoisie() === $option)->count(); }
    public function getPublieeLe(): \DateTimeImmutable { return $this->publieeLe; }
    /** @return Collection<int, Utilisateur> */
    public function getAimePar(): Collection { return $this->aimePar; }
    public function ajouterAime(Utilisateur $utilisateur): static { if (!$this->aimePar->contains($utilisateur)) { $this->aimePar->add($utilisateur); } return $this; }
    public function retirerAime(Utilisateur $utilisateur): static { $this->aimePar->removeElement($utilisateur); return $this; }
    public function estAimePar(Utilisateur $utilisateur): bool { return $this->aimePar->contains($utilisateur); }
}
