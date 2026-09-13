<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SuiviJeuController extends AbstractController
{
    #[Route('/jeu/{id}/suivre', name: 'app_jeu_suivre', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function basculer(Jeu $jeu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        if ($jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException('Ce jeu n’existe pas.');
        }
        if (!$this->isCsrfTokenValid('suivre-jeu-'.$jeu->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($utilisateur->suitJeu($jeu)) {
            $utilisateur->nePlusSuivreJeu($jeu);
            $message = 'Vous ne suivez plus ce jeu.';
        } else {
            $utilisateur->suivreJeu($jeu);
            $message = 'Vous serez averti des mises à jour importantes de ce jeu.';
        }
        $entityManager->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_jeu_show', ['slug' => $jeu->getSlug(), 'id' => $jeu->getId()]);
    }
}
