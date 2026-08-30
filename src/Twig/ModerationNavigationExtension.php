<?php

namespace App\Twig;

use App\Enum\StatutJeu;
use App\Enum\StatutSignalement;
use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use App\Repository\SignalementRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class ModerationNavigationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly JeuRepository $jeuRepository,
        private readonly ActualiteRepository $actualiteRepository,
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    public function getGlobals(): array
    {
        if (!$this->security->isGranted('ROLE_MODERATEUR')) {
            return [
                'jeux_en_attente_moderation' => 0,
                'actualites_en_attente_moderation' => 0,
                'signalements_en_attente_moderation' => 0,
            ];
        }

        return [
            'jeux_en_attente_moderation' => $this->jeuRepository->count(['statut' => StatutJeu::EnAttente]),
            'actualites_en_attente_moderation' => $this->actualiteRepository->compterEnAttente(),
            'signalements_en_attente_moderation' => $this->signalementRepository->count(['statut' => StatutSignalement::EnAttente]),
        ];
    }
}
