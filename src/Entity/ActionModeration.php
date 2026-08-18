<?php
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Index(columns: ['effectuee_le'], name: 'action_moderation_date_idx')]
class ActionModeration
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')] private ?Utilisateur $moderateur = null;
    #[ORM\Column(length: 80)] private string $action;
    #[ORM\Column(length: 40)] private string $typeCible;
    #[ORM\Column(nullable: true)] private ?int $cibleId = null;
    #[ORM\Column(length: 255)] private string $resume;
    #[ORM\Column(type: Types::JSON)] private array $details = [];
    #[ORM\Column] private \DateTimeImmutable $effectueeLe;
    public function __construct(?Utilisateur $moderateur, string $action, string $typeCible, ?int $cibleId, string $resume, array $details = []) { $this->moderateur = $moderateur; $this->action = $action; $this->typeCible = $typeCible; $this->cibleId = $cibleId; $this->resume = $resume; $this->details = $details; $this->effectueeLe = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getModerateur(): ?Utilisateur { return $this->moderateur; }
    public function getAction(): string { return $this->action; }
    public function getTypeCible(): string { return $this->typeCible; }
    public function getCibleId(): ?int { return $this->cibleId; }
    public function getResume(): string { return $this->resume; }
    public function getDetails(): array { return $this->details; }
    public function getEffectueeLe(): \DateTimeImmutable { return $this->effectueeLe; }
}
