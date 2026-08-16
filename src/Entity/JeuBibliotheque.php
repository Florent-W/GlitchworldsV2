<?php

namespace App\Entity;

use App\Enum\StatutBibliotheque;
use App\Repository\JeuBibliothequeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JeuBibliothequeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_BIBLIOTHEQUE_MEMBRE_JEU', columns: ['utilisateur_id', 'jeu_id'])]
class JeuBibliotheque
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Jeu $jeu = null;
    #[ORM\Column(enumType: StatutBibliotheque::class)] private StatutBibliotheque $statut = StatutBibliotheque::A_Jouer;
    #[ORM\Column] private \DateTimeImmutable $ajouteLe;

    public function __construct() { $this->ajouteLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getJeu(): ?Jeu { return $this->jeu; }
    public function setJeu(Jeu $jeu): static { $this->jeu = $jeu; return $this; }
    public function getStatut(): StatutBibliotheque { return $this->statut; }
    public function setStatut(StatutBibliotheque $statut): static { $this->statut = $statut; return $this; }
    public function getAjouteLe(): \DateTimeImmutable { return $this->ajouteLe; }
}
