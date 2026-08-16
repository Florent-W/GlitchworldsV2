<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\Publication;
use App\Form\PublicationType;
use App\Enum\StatutJeu;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommunauteController extends AbstractController
{
    #[Route('/communaute', name: 'app_communaute', methods: ['GET'])]
    public function index(
        Request $request,
        CommentaireJeuRepository $commentaireJeuRepository,
        CommentaireActualiteRepository $commentaireActualiteRepository,
        JeuRepository $jeuRepository,
        ActualiteRepository $actualiteRepository,
        UtilisateurRepository $utilisateurRepository,
        PublicationRepository $publicationRepository,
    ): Response {
        $section = in_array($request->query->getString('section'), ['fil', 'discussions', 'creations', 'actualites'], true)
            ? $request->query->getString('section')
            : 'fil';
        $page = max(1, $request->query->getInt('page', 1));
        $publications = $publicationRepository->trouverDernieres(50);
        $commentairesJeux = $commentaireJeuRepository->trouverDerniersPublics(50);
        $commentairesActualites = $commentaireActualiteRepository->trouverDerniersPublics(50);
        $nouveauxJeux = $jeuRepository->trouverNouveautes(12);
        $dernieresActualites = $actualiteRepository->trouverDernieres(12);
        $activites = [];

        if ('fil' === $section) {
            foreach ($publications as $publication) { $activites[] = ['type' => 'publication', 'date' => $publication->getPublieeLe(), 'element' => $publication]; }
        }
        if (in_array($section, ['fil', 'discussions'], true)) {
            foreach ($commentairesJeux as $commentaire) { $activites[] = ['type' => 'commentaire_jeu', 'date' => $commentaire->getDateCommentaire(), 'element' => $commentaire]; }
            foreach ($commentairesActualites as $commentaire) { $activites[] = ['type' => 'commentaire_actualite', 'date' => $commentaire->getDateCommentaire(), 'element' => $commentaire]; }
        }
        if ('creations' === $section) {
            foreach ($nouveauxJeux as $jeu) { $activites[] = ['type' => 'jeu', 'date' => $jeu->getCreeLe(), 'element' => $jeu]; }
        }
        if ('actualites' === $section) {
            foreach ($dernieresActualites as $actualite) { $activites[] = ['type' => 'actualite', 'date' => $actualite->getPublieeLe(), 'element' => $actualite]; }
        }
        usort($activites, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);
        $totalActivites = count($activites);
        $parPage = 10;
        $pages = max(1, (int) ceil($totalActivites / $parPage));
        $page = min($page, $pages);
        $activites = array_slice($activites, ($page - 1) * $parPage, $parPage);

        return $this->render('communaute/index.html.twig', [
            'nouveauxJeux' => array_slice($nouveauxJeux, 0, 4),
            'dernieresActualites' => array_slice($dernieresActualites, 0, 4),
            'statistiques' => [
                'membres' => $utilisateurRepository->count([]),
                'jeux' => $jeuRepository->count(['statut' => StatutJeu::Approuve]),
                'commentaires' => $commentaireJeuRepository->compterPublics() + $commentaireActualiteRepository->compterPublics(),
            ],
            'membresEnLigne' => $utilisateurRepository->trouverEnLigne(),
            'section' => $section,
            'activites' => $activites,
            'page' => $page,
            'pages' => $pages,
            'totalActivites' => $totalActivites,
            'formulairePublication' => $this->getUser() ? $this->createForm(PublicationType::class, new Publication(), [
                'action' => $this->generateUrl('app_publication_creer'),
            ])->createView() : null,
        ]);
    }
}
