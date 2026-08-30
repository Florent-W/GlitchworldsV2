<?php

namespace App\Twig;

use App\Entity\Utilisateur;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    public function getGlobals(): array
    {
        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            return ['notifications_non_lues' => 0];
        }

        return ['notifications_non_lues' => $this->notificationRepository->compterNonLues($utilisateur)];
    }
}
