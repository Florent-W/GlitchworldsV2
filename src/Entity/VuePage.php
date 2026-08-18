<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Index(columns: ['vue_le'], name: 'vue_page_date_idx')]
#[ORM\Index(columns: ['type_contenu', 'contenu_id', 'vue_le'], name: 'vue_page_contenu_idx')]
class VuePage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 180)] private string $chemin;
    #[ORM\Column(length: 20)] private string $typeContenu = 'page';
    #[ORM\Column(nullable: true)] private ?int $contenuId = null;
    #[ORM\Column(length: 64)] private string $visiteurHash;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?Utilisateur $utilisateur = null;
    #[ORM\Column] private \DateTimeImmutable $vueLe;
    public function __construct(string $chemin, string $typeContenu, ?int $contenuId, string $visiteurHash, ?Utilisateur $utilisateur) { $this->chemin = $chemin; $this->typeContenu = $typeContenu; $this->contenuId = $contenuId; $this->visiteurHash = $visiteurHash; $this->utilisateur = $utilisateur; $this->vueLe = new \DateTimeImmutable(); }
}
