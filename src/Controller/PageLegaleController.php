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
}
