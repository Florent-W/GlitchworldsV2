<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\JeuRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfilController extends AbstractController
{
    #[Route('/membre/{id}', name: 'app_profil', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function voir(Utilisateur $membre, Request $request, PublicationRepository $publications, JeuRepository $jeux): Response
    {
        $section = $request->query->getString('section', 'apropos');
        if (!in_array($section, ['apropos', 'activite', 'jeux', 'favoris'], true)) { $section = 'apropos'; }

        return $this->render('profil/voir.html.twig', [
            'membre' => $membre,
            'section' => $section,
            'publications' => $publications->trouverPourAuteur($membre),
            'jeux' => $jeux->trouverApprouvesPar($membre),
        ]);
    }

    #[Route('/membre/{id}/suivre', name: 'app_profil_suivre', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function suivre(Utilisateur $membre, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }
        if ($utilisateur === $membre) { throw $this->createAccessDeniedException('Tu ne peux pas suivre ton propre profil.'); }
        if (!$this->isCsrfTokenValid('suivre-'.$membre->getId(), (string) $request->request->get('_token'))) { throw $this->createAccessDeniedException('Jeton CSRF invalide.'); }
        $utilisateur->suit($membre) ? $utilisateur->nePlusSuivre($membre) : $utilisateur->suivre($membre);
        $entityManager->flush();

        return $this->redirectToRoute('app_profil', ['id' => $membre->getId()]);
    }
}
