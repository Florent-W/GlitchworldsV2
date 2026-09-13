<?php

namespace App\Controller;

use App\Entity\ListeJeux;
use App\Enum\StatutJeu;
use App\Repository\AvisRepository;
use App\Repository\ListeJeuxRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListePubliqueController extends AbstractController
{
    #[Route('/listes', name: 'app_listes_publiques', methods: ['GET'])]
    public function index(Request $request, ListeJeuxRepository $listeJeuxRepository): Response
    {
        $recherche = trim($request->query->getString('q'));

        return $this->render('liste_publique/index.html.twig', [
            'pagination' => $listeJeuxRepository->trouverPubliquesPaginees($request->query->getInt('page', 1), 12, $recherche),
            'recherche' => $recherche,
        ]);
    }

    #[Route('/liste/{slug}-{id}', name: 'app_liste_publique', requirements: ['slug' => '[a-z0-9-]+', 'id' => '\\d+'], methods: ['GET'])]
    public function voir(string $slug, ListeJeux $liste, AvisRepository $avisRepository): Response
    {
        if (!$liste->isPublique() || $liste->getSlug() === null) {
            throw $this->createNotFoundException('Cette liste n’est pas publique.');
        }
        if ($slug !== $liste->getSlug()) {
            return $this->redirectToRoute('app_liste_publique', ['slug' => $liste->getSlug(), 'id' => $liste->getId()], Response::HTTP_MOVED_PERMANENTLY);
        }
        $jeux = array_values(array_filter(
            $liste->getJeux(),
            static fn ($jeu): bool => $jeu->getStatut() === StatutJeu::Approuve,
        ));

        return $this->render('liste_publique/voir.html.twig', [
            'liste' => $liste,
            'jeux' => $jeux,
            'notesJeux' => $avisRepository->trouverResumesPour($jeux),
        ]);
    }
}
