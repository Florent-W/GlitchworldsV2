<?php

namespace App\Entity;

use App\Repository\ListeJeuxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ListeJeuxRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['publique', 'modifie_le'], name: 'liste_jeux_publique_idx')]
class ListeJeux
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column(length: 80)] #[Assert\NotBlank, Assert\Length(max: 80)] private string $nom = '';
    #[ORM\Column(length: 255, nullable: true)] private ?string $description = null;
    #[ORM\Column(length: 100, nullable: true)] private ?string $slug = null;
    #[ORM\Column(options: ['default' => 0])] private bool $publique = false;
    /** @var Collection<int, ListeJeuxElement> */
    #[ORM\OneToMany(mappedBy: 'liste', targetEntity: ListeJeuxElement::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $elements;
    #[ORM\Column] private \DateTimeImmutable $creeLe;
    #[ORM\Column] private \DateTimeImmutable $modifieLe;

    public function __construct() { $this->elements = new ArrayCollection(); $this->creeLe = new \DateTimeImmutable(); $this->modifieLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = trim($nom); return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description === null ? null : trim($description); return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): static { $this->slug = $slug; return $this; }
    public function isPublique(): bool { return $this->publique; }
    public function setPublique(bool $publique): static { $this->publique = $publique; return $this->touch(); }
    /** @return list<Jeu> */ public function getJeux(): array { return array_values(array_filter(array_map(static fn (ListeJeuxElement $element) => $element->getJeu(), $this->elements->toArray()))); }
    public function ajouterJeu(Jeu $jeu): static { if (!$this->contient($jeu)) { $position = 1; foreach ($this->elements as $element) { $position = max($position, $element->getPosition() + 1); } $this->elements->add((new ListeJeuxElement())->setListe($this)->setJeu($jeu)->setPosition($position)); } return $this; }
    public function retirerJeu(Jeu $jeu): static { foreach ($this->elements as $element) { if ($element->getJeu() === $jeu) { $this->elements->removeElement($element); break; } } return $this; }
    public function contient(Jeu $jeu): bool { foreach ($this->elements as $element) { if ($element->getJeu() === $jeu) { return true; } } return false; }
    public function deplacerJeu(Jeu $jeu, int $decalage): bool { $elements = $this->elements->toArray(); $index = null; foreach ($elements as $i => $element) { if ($element->getJeu() === $jeu) { $index = $i; break; } } $destination = $index === null ? -1 : $index + $decalage; if ($index === null || !isset($elements[$destination])) { return false; } $position = $elements[$index]->getPosition(); $elements[$index]->setPosition($elements[$destination]->getPosition()); $elements[$destination]->setPosition($position); return true; }
    /** @param list<int> $ids */ public function reordonnerJeux(array $ids): bool { $elements = []; foreach ($this->elements as $element) { $id = $element->getJeu()?->getId(); if ($id !== null) { $elements[$id] = $element; } } if (count($ids) !== count($elements) || array_diff($ids, array_keys($elements)) !== [] || count(array_unique($ids)) !== count($ids)) { return false; } foreach ($ids as $position => $id) { $elements[$id]->setPosition($position + 1); } return true; }
    public function getCreeLe(): \DateTimeImmutable { return $this->creeLe; }
    public function getModifieLe(): \DateTimeImmutable { return $this->modifieLe; }
    #[ORM\PreUpdate]
    public function touch(): static { $this->modifieLe = new \DateTimeImmutable(); return $this; }
}
