<?php

namespace App\Controller;

use App\Service\BbcodeConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class BbcodeController extends AbstractController
{
    #[Route('/bbcode/apercu', name: 'app_bbcode_apercu', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function apercu(Request $request, BbcodeConverter $convertisseur): Response
    {
        if (!$this->isCsrfTokenValid('apercu-bbcode', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $contenu = mb_substr($request->request->getString('contenu'), 0, 100_000);

        return new Response($convertisseur->toHtml($contenu));
    }
}
