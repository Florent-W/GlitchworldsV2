<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Asset\Packages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RechercheController extends AbstractController
{
    #[Route('/recherche', name: 'app_recherche', methods: ['GET'])]
    public function rechercher(Request $request, JeuRepository $jeuRepository, ActualiteRepository $actualiteRepository): Response
    {
        $recherche = trim((string) $request->query->get('recherche', ''));

        return $this->render('recherche/index.html.twig', [
            'recherche' => $recherche,
            'jeux' => '' !== $recherche ? $jeuRepository->rechercherPourApercu($recherche) : [],
            'actualites' => '' !== $recherche ? $actualiteRepository->rechercherPourApercu($recherche) : [],
        ]);
    }

    #[Route('/recherche/autocompletion', name: 'app_recherche_autocompletion', methods: ['GET'])]
    public function autocompletion(
        Request $request,
        JeuRepository $jeuRepository,
        ActualiteRepository $actualiteRepository,
        UtilisateurRepository $utilisateurRepository,
        Packages $assets,
    ): JsonResponse {
        $recherche = trim($request->query->getString('recherche'));
        $type = $request->query->getString('type');
        if (!in_array($type, ['', 'jeu', 'actualite'], true)) {
            $type = '';
        }
        if (mb_strlen($recherche) < 2) {
            return $this->json(['resultats' => []]);
        }

        $resultats = [];
        foreach (in_array($type, ['', 'jeu'], true) ? $jeuRepository->rechercherPourApercu($recherche, 5) : [] as $jeu) {
            $resultats[] = [
                'type' => 'Jeu',
                'icone' => 'controller',
                'titre' => $jeu->getNom(),
                'detail' => $jeu->getDeveloppeur() ? 'Par '.$jeu->getDeveloppeur() : 'Fiche de jeu',
                'miniature' => str_starts_with((string) $jeu->getMiniature(), 'miniature.')
                    ? $assets->getUrl('uploads/jeux/'.$jeu->getId().'/'.$jeu->getMiniature())
                    : null,
                'url' => $this->generateUrl('app_jeu_show', ['slug' => $jeu->getSlug(), 'id' => $jeu->getId()]),
            ];
        }
        foreach (in_array($type, ['', 'actualite'], true) ? $actualiteRepository->rechercherPourApercu($recherche, 5) : [] as $actualite) {
            $resultats[] = [
                'type' => 'Actualité',
                'icone' => 'newspaper',
                'titre' => $actualite->getTitre(),
                'detail' => $actualite->getCategorie()->label(),
                'miniature' => $actualite->getMiniature() && !str_starts_with($actualite->getMiniature(), 'legacy:')
                    ? $assets->getUrl('uploads/actualites/'.$actualite->getId().'/'.$actualite->getMiniature())
                    : null,
                'url' => $this->generateUrl('app_actualite_voir', ['slug' => $actualite->getSlug(), 'id' => $actualite->getId()]),
            ];
        }
        foreach ('' === $type ? $utilisateurRepository->rechercherParPseudo($recherche, 4) : [] as $membre) {
            $resultats[] = [
                'type' => 'Membre',
                'icone' => 'person-fill',
                'titre' => $membre->getPseudo(),
                'detail' => 'Profil membre',
                'miniature' => str_starts_with((string) $membre->getAvatar(), 'avatar.')
                    ? $assets->getUrl('uploads/utilisateurs/'.$membre->getId().'/'.$membre->getAvatar())
                    : null,
                'url' => $this->generateUrl('app_profil', ['id' => $membre->getId()]),
            ];
        }

        return $this->json(['resultats' => $resultats]);
    }
}
