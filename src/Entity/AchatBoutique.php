<?php

namespace App\Entity;

use App\Repository\AchatBoutiqueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AchatBoutiqueRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_ACHAT_BOUTIQUE_MEMBRE_ARTICLE', columns: ['utilisateur_id', 'article_id'])]
class AchatBoutique
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArticleBoutique $article = null;
    #[ORM\Column] private int $prixPaye = 0;
    #[ORM\Column] private \DateTimeImmutable $acheteLe;

    public function __construct() { $this->acheteLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getArticle(): ?ArticleBoutique { return $this->article; }
    public function setArticle(ArticleBoutique $article): static { $this->article = $article; return $this; }
    public function getPrixPaye(): int { return $this->prixPaye; }
    public function setPrixPaye(int $prix): static { $this->prixPaye = $prix; return $this; }
    public function getAcheteLe(): \DateTimeImmutable { return $this->acheteLe; }
}
