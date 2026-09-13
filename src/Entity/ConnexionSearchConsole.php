<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ConnexionSearchConsole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $jetonAcces = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $jetonRafraichissement = null;

    #[ORM\Column]
    private \DateTimeImmutable $expireLe;

    #[ORM\Column]
    private \DateTimeImmutable $connecteLe;

    public function __construct()
    {
        $this->expireLe = new \DateTimeImmutable();
        $this->connecteLe = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getJetonAcces(): string { return $this->jetonAcces; }
    public function setJetonAcces(string $jeton): static { $this->jetonAcces = $jeton; return $this; }
    public function getJetonRafraichissement(): ?string { return $this->jetonRafraichissement; }
    public function setJetonRafraichissement(?string $jeton): static { $this->jetonRafraichissement = $jeton; return $this; }
    public function getExpireLe(): \DateTimeImmutable { return $this->expireLe; }
    public function setExpireLe(\DateTimeImmutable $date): static { $this->expireLe = $date; return $this; }
    public function getConnecteLe(): \DateTimeImmutable { return $this->connecteLe; }
    public function setConnecteLe(\DateTimeImmutable $date): static { $this->connecteLe = $date; return $this; }
}
