<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Repository\JeuRepository;
use App\Service\ProgressionUtilisateur;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavoriController extends AbstractController
{
    use AnnonceSuccesTrait;
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
    public function basculer(Jeu $jeu, Request $request, EntityManagerInterface $entityManager, ProgressionUtilisateur $progression, GestionSucces $gestionSucces): Response
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
            $entityManager->flush();
        } else {
            $utilisateur->ajouterJeuFavori($jeu);
            $recompense = $progression->recompenseFavori($utilisateur, (int) $jeu->getId());
            $message = 'Le jeu a été ajouté à tes favoris.'.($recompense ? ' +3 XP et +1 point.' : '');
            $entityManager->flush();
            $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
        }

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_jeu_show', [
            'slug' => $jeu->getSlug(),
            'id' => $jeu->getId(),
        ]);
    }
}
