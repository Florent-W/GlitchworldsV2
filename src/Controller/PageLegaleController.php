<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PageLegaleController extends AbstractController
{
    #[Route('/rgpd', name: 'app_rgpd', methods: ['GET'])]
    #[Route('/politique-de-confidentialite', name: 'app_confidentialite', methods: ['GET'])]
    public function rgpd(): Response
    {
        return $this->render('pages/rgpd.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales', methods: ['GET'])]
    public function mentionsLegales(): Response
    {
        return $this->render('pages/mentions-legales.html.twig');
    }

    #[Route('/conditions-utilisation', name: 'app_conditions_utilisation', methods: ['GET'])]
    public function conditionsUtilisation(): Response
    {
        return $this->render('pages/conditions-utilisation.html.twig');
    }

    #[Route('/charte-moderation', name: 'app_charte_moderation', methods: ['GET'])]
    #[Route('/charte-contribution', name: 'app_charte_contribution', methods: ['GET'])]
    public function charteModeration(): Response
    {
        return $this->render('pages/charte-moderation.html.twig');
    }
}
