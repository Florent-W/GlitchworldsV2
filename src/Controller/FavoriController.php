<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Repository\JeuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavoriController extends AbstractController
{
    #[Route('/favoris', name: 'app_favoris', methods: ['GET'])]
    public function liste(Request $request, JeuRepository $jeuRepository): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('favori/index.html.twig', $jeuRepository->trouverFavorisPagines(
            $utilisateur,
            $request->query->getInt('page', 1),
        ));
    }

    #[Route('/jeu/{id}/favori', name: 'app_jeu_favori', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function basculer(Jeu $jeu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        if ($jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException('Ce jeu n’existe pas.');
        }
        if (!$this->isCsrfTokenValid('favori-jeu-'.$jeu->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($utilisateur->aPourFavori($jeu)) {
            $utilisateur->retirerJeuFavori($jeu);
            $message = 'Le jeu a été retiré de tes favoris.';
        } else {
            $utilisateur->ajouterJeuFavori($jeu);
            $message = 'Le jeu a été ajouté à tes favoris.';
        }

        $entityManager->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_jeu_show', [
            'slug' => $jeu->getSlug(),
            'id' => $jeu->getId(),
        ]);
    }
}
