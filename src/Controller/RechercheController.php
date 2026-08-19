<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\CategorieJeuRepository;
use App\Repository\PlateformeRepository;
use App\Repository\LangueRepository;
use App\Repository\GenreRepository;
use App\Enum\TriJeu;
use App\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Asset\Packages;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RechercheController extends AbstractController
{
    #[Route('/recherche', name: 'app_recherche', methods: ['GET'])]
    public function rechercher(Request $request, JeuRepository $jeuRepository, ActualiteRepository $actualiteRepository, UtilisateurRepository $utilisateurRepository, CategorieJeuRepository $categorieRepository, PlateformeRepository $plateformeRepository, GenreRepository $genreRepository, LangueRepository $langueRepository): Response
    {
        $recherche = trim($request->query->getString('recherche'));
        $type = $request->query->getString('type');
        if (!in_array($type, ['', 'jeu', 'actualite', 'membre'], true)) { $type = ''; }
        $categorie = $request->query->getString('categorie');
        $plateforme = $request->query->getString('plateforme');
        $genre = $request->query->getString('genre');
        $langue = $request->query->getString('langue');
        $anneeBrute = trim($request->query->getString('annee'));
        $annee = ctype_digit($anneeBrute) ? (int) $anneeBrute : null;
        $mesFavoris = $request->query->getBoolean('mes_favoris');
        $tri = TriJeu::tryFrom($request->query->getString('tri', 'recent')) ?? TriJeu::Recent;
        $utilisateur = $this->getUser();
        $paginationJeux = '' !== $recherche && in_array($type, ['', 'jeu'], true)
            ? $jeuRepository->trouverApprouvesPagines(
                1,
                12,
                $recherche,
                $categorie,
                $plateforme,
                $genre,
                $langue,
                $tri,
                $annee,
                $mesFavoris,
                $utilisateur instanceof Utilisateur ? $utilisateur : null,
            )
            : ['jeux' => [], 'total' => 0];
        $actualites = '' !== $recherche && in_array($type, ['', 'actualite'], true) ? $actualiteRepository->rechercherPourApercu($recherche, 12) : [];
        $membres = '' !== $recherche && in_array($type, ['', 'membre'], true) ? $utilisateurRepository->rechercherParPseudo($recherche, 12) : [];
        $totalActualites = '' !== $recherche && in_array($type, ['', 'actualite'], true) ? $actualiteRepository->compterPourApercu($recherche) : 0;
        $totalMembres = '' !== $recherche && in_array($type, ['', 'membre'], true) ? $utilisateurRepository->compterParPseudo($recherche) : 0;

        return $this->render('recherche/index.html.twig', [
            'recherche' => $recherche,
            'type' => $type, 'categorie' => $categorie, 'plateforme' => $plateforme, 'genre' => $genre, 'langue' => $langue,
            'annee' => $annee, 'mesFavoris' => $mesFavoris, 'tri' => $tri,
            'jeux' => $paginationJeux['jeux'], 'totalJeux' => $paginationJeux['total'], 'actualites' => $actualites, 'membres' => $membres,
            'totalActualites' => $totalActualites, 'totalMembres' => $totalMembres,
            'categories' => $categorieRepository->trouverToutes(), 'plateformes' => $plateformeRepository->trouverToutes(), 'genres' => $genreRepository->trouverTous(), 'langues' => $langueRepository->trouverToutes(),
            'annees' => $jeuRepository->listerAnneesSortie(), 'tris' => TriJeu::cases(),
            'totalResultats' => $paginationJeux['total'] + $totalActualites + $totalMembres,
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
            return $this->json(['resultats' => [], 'totaux' => [], 'total' => 0]);
        }

        $resultats = [];
        foreach (in_array($type, ['', 'jeu'], true) ? $jeuRepository->rechercherPourApercu($recherche, 5) : [] as $jeu) {
            $resultats[] = [
                'type' => 'Jeu',
                'icone' => 'controller',
                'titre' => $jeu->getNom(),
                'detail' => $jeu->getDeveloppeur() ? 'Auteur : '.$jeu->getDeveloppeur() : 'Fiche de jeu',
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

        $totaux = [];
        if (in_array($type, ['', 'jeu'], true)) {
            $totaux['Jeu'] = $jeuRepository->compterPourApercu($recherche);
        }
        if (in_array($type, ['', 'actualite'], true)) {
            $totaux['Actualité'] = $actualiteRepository->compterPourApercu($recherche);
        }
        if ('' === $type) {
            $totaux['Membre'] = $utilisateurRepository->compterParPseudo($recherche);
        }

        return $this->json([
            'resultats' => $resultats,
            'totaux' => array_filter($totaux),
            'total' => array_sum($totaux),
        ]);
    }
}
