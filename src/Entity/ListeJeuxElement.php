<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'liste_jeux_element')]
class ListeJeuxElement
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'liste_jeux_id', nullable: false, onDelete: 'CASCADE')]
    private ?ListeJeux $liste = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Jeu $jeu = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    public function getListe(): ?ListeJeux { return $this->liste; }
    public function setListe(ListeJeux $liste): static { $this->liste = $liste; return $this; }
    public function getJeu(): ?Jeu { return $this->jeu; }
    public function setJeu(Jeu $jeu): static { $this->jeu = $jeu; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}
