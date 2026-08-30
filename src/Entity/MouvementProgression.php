<?php
namespace App\Entity;
use App\Repository\MouvementProgressionRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: MouvementProgressionRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_MOUVEMENT_SOURCE_MEMBRE', columns: ['utilisateur_id', 'cle_source'])]
#[ORM\Index(columns: ['utilisateur_id', 'cree_le'], name: 'mouvement_membre_date_idx')]
class MouvementProgression
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column(length: 40)] private string $categorie;
    #[ORM\Column(length: 191)] private string $cleSource;
    #[ORM\Column(length: 255)] private string $libelle;
    #[ORM\Column] private int $experience = 0;
    #[ORM\Column] private int $points = 0;
    #[ORM\Column] private \DateTimeImmutable $creeLe;
    public function __construct(Utilisateur $utilisateur, string $categorie, string $cleSource, string $libelle, int $experience, int $points) { $this->utilisateur = $utilisateur; $this->categorie = $categorie; $this->cleSource = $cleSource; $this->libelle = $libelle; $this->experience = $experience; $this->points = $points; $this->creeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getCategorie(): string { return $this->categorie; }
    public function getLibelle(): string { return $this->libelle; }
    public function getExperience(): int { return $this->experience; }
    public function getPoints(): int { return $this->points; }
    public function getCreeLe(): \DateTimeImmutable { return $this->creeLe; }
}
