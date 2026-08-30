<?php

namespace App\Entity;

use App\Repository\SuccesUtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuccesUtilisateurRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SUCCES_MEMBRE', columns: ['utilisateur_id', 'succes_id'])]
class SuccesUtilisateur
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Succes $succes = null;
    #[ORM\Column] private \DateTimeImmutable $debloqueLe;
    public function __construct() { $this->debloqueLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getSucces(): ?Succes { return $this->succes; }
    public function setSucces(Succes $succes): static { $this->succes = $succes; return $this; }
    public function getDebloqueLe(): \DateTimeImmutable { return $this->debloqueLe; }
}
