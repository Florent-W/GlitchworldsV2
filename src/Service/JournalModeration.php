<?php
namespace App\Service;
use App\Entity\ActionModeration;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
final class JournalModeration
{
    public function __construct(private EntityManagerInterface $em) {}
    public function ajouter(?Utilisateur $moderateur, string $action, string $typeCible, ?int $cibleId, string $resume, array $details = []): void { $this->em->persist(new ActionModeration($moderateur, $action, $typeCible, $cibleId, $resume, $details)); }
}
