<?php

namespace App\Controller;

use App\Repository\ActualiteRepository;
use App\Repository\AvisRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\PublicationRepository;
use App\Repository\UtilisateurRepository;
use App\Entity\Publication;
use App\Entity\Utilisateur;
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
        AvisRepository $avisRepository,
    ): Response {
        $utilisateur = $this->getUser();
        $sections = ['fil', 'discussions', 'creations', 'actualites'];
        if ($utilisateur instanceof Utilisateur) {
            $sections[] = 'abonnements';
        }
        $section = in_array($request->query->getString('section'), $sections, true)
            ? $request->query->getString('section')
            : 'fil';
        $page = max(1, $request->query->getInt('page', 1));
        $publications = $publicationRepository->trouverDernieres(50);
        $commentairesJeux = $commentaireJeuRepository->trouverDerniersPublics(50);
        $commentairesActualites = $commentaireActualiteRepository->trouverDerniersPublics(50);
        $nouveauxJeux = $jeuRepository->trouverNouveautes(12);
        $dernieresActualites = $actualiteRepository->trouverDernieres(12);
        $dernieresNotes = 'abonnements' === $section ? $avisRepository->trouverDernieresNotes(50) : [];
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
        if ('abonnements' === $section && $utilisateur instanceof Utilisateur) {
            $activites = $this->filtrerSurLesSuivis($utilisateur, $publications, $commentairesJeux, $commentairesActualites, $nouveauxJeux, $dernieresActualites, $dernieresNotes);
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
            'suivisCount' => $utilisateur instanceof Utilisateur ? $utilisateur->getAbonnements()->count() : 0,
        ]);
    }

    /**
     * Regroupe toute l'activité des membres suivis, quel que soit son type :
     * l'onglet n'a d'intérêt que s'il rassemble ce qui est éparpillé ailleurs.
     *
     * @param list<mixed> $publications
     * @param list<mixed> $commentairesJeux
     * @param list<mixed> $commentairesActualites
     * @param list<mixed> $nouveauxJeux
     * @param list<mixed> $dernieresActualites
     * @param list<mixed> $dernieresNotes
     *
     * @return list<array{type: string, date: \DateTimeInterface, element: mixed}>
     */
    private function filtrerSurLesSuivis(
        Utilisateur $utilisateur,
        array $publications,
        array $commentairesJeux,
        array $commentairesActualites,
        array $nouveauxJeux,
        array $dernieresActualites,
        array $dernieresNotes,
    ): array {
        $suivis = $utilisateur->getAbonnements();
        $activites = [];

        foreach ($publications as $publication) {
            if ($publication->getAuteur() !== null && $suivis->contains($publication->getAuteur())) {
                $activites[] = ['type' => 'publication', 'date' => $publication->getPublieeLe(), 'element' => $publication];
            }
        }
        foreach ($commentairesJeux as $commentaire) {
            if ($commentaire->getAuteur() !== null && $suivis->contains($commentaire->getAuteur())) {
                $activites[] = ['type' => 'commentaire_jeu', 'date' => $commentaire->getDateCommentaire(), 'element' => $commentaire];
            }
        }
        foreach ($commentairesActualites as $commentaire) {
            if ($commentaire->getAuteur() !== null && $suivis->contains($commentaire->getAuteur())) {
                $activites[] = ['type' => 'commentaire_actualite', 'date' => $commentaire->getDateCommentaire(), 'element' => $commentaire];
            }
        }
        foreach ($nouveauxJeux as $jeu) {
            if ($jeu->getCreateur() !== null && $suivis->contains($jeu->getCreateur())) {
                $activites[] = ['type' => 'jeu', 'date' => $jeu->getCreeLe(), 'element' => $jeu];
            }
        }
        foreach ($dernieresActualites as $actualite) {
            if ($actualite->getAuteur() !== null && $suivis->contains($actualite->getAuteur())) {
                $activites[] = ['type' => 'actualite', 'date' => $actualite->getPublieeLe(), 'element' => $actualite];
            }
        }
        foreach ($dernieresNotes as $avis) {
            if ($avis->getAuteur() !== null && $suivis->contains($avis->getAuteur())) {
                $activites[] = ['type' => 'note', 'date' => $avis->getDateAvis(), 'element' => $avis];
            }
        }

        return $activites;
    }
}
