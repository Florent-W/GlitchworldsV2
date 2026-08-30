<?php

namespace App\Controller;

use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Enum\TriJeu;
use App\Form\CommentaireJeuType;
use App\Form\NoteJeuType;
use App\Repository\CategorieJeuRepository;
use App\Repository\AvisRepository;
use App\Repository\ActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\GenreRepository;
use App\Repository\JeuRepository;
use App\Repository\LangueRepository;
use App\Repository\PlateformeRepository;
use App\Service\ProgressionUtilisateur;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Attribute\Route;

final class JeuController extends AbstractController
{
    use AnnonceSuccesTrait;
    #[Route('/jeux', name: 'app_jeux')]
    public function liste(
        Request $request,
        JeuRepository $jeuRepository,
        CategorieJeuRepository $categorieJeuRepository,
        PlateformeRepository $plateformeRepository,
        GenreRepository $genreRepository,
        LangueRepository $langueRepository,
        AvisRepository $avisRepository,
    ): Response {
        $page = $request->query->getInt('page', 1);
        $recherche = trim((string) $request->query->get('recherche', ''));
        $categorie = trim((string) $request->query->get('categorie', ''));
        $plateforme = trim((string) $request->query->get('plateforme', ''));
        $genre = trim((string) $request->query->get('genre', ''));
        $langue = trim((string) $request->query->get('langue', ''));
        $anneeBrute = trim((string) $request->query->get('annee', ''));
        $annee = ctype_digit($anneeBrute) ? (int) $anneeBrute : null;
        $mesFavoris = $request->query->getBoolean('mes_favoris');
        $tri = TriJeu::tryFrom((string) $request->query->get('tri', 'recent')) ?? TriJeu::Recent;
        $utilisateur = $this->getUser();
        $pagination = $jeuRepository->trouverApprouvesPagines(
            $page,
            20,
            $recherche,
            $categorie,
            $plateforme,
            $genre,
            $langue,
            $tri,
            $annee,
            $mesFavoris,
            $utilisateur instanceof Utilisateur ? $utilisateur : null,
        );

        return $this->render('jeu/index.html.twig', [
            ...$pagination,
            'notesJeux' => $avisRepository->trouverResumesPour($pagination['jeux']),
            'categories' => $categorieJeuRepository->trouverToutes(),
            'plateformes' => $plateformeRepository->trouverToutes(),
            'genres' => $genreRepository->trouverTous(),
            'langues' => $langueRepository->trouverToutes(),
            'annees' => $jeuRepository->listerAnneesSortie(),
            'tris' => TriJeu::cases(),
        ]);
    }

    #[Route('/jeu/{slug}-{id}', name: 'app_jeu_show', requirements: ['id' => '\d+', 'slug' => '[a-z0-9\-]+'])]
    public function show(
        Request $request,
        string $slug,
        int $id,
        JeuRepository $jeuRepository,
        AvisRepository $avisRepository,
        CommentaireJeuRepository $commentaireJeuRepository,
        EntityManagerInterface $entityManager,
        ActualiteRepository $actualiteRepository,
        ProgressionUtilisateur $progression,
        GestionSucces $gestionSucces,
    ): Response
    {
        $jeu = $jeuRepository->find($id);

        if (!$jeu instanceof Jeu || $jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException('Ce jeu n\'existe pas.');
        }

        if ($jeu->getSlug() !== $slug) {
            return $this->redirectToRoute('app_jeu_show', [
                'slug' => $jeu->getSlug(),
                'id' => $jeu->getId(),
            ], 301);
        }

        $auteurFiche = $jeu->getCreateur();
        $commentaire = new CommentaireJeu();
        $formulaireCommentaire = $this->createForm(CommentaireJeuType::class, $commentaire, [
            'disabled' => !$this->getUser() instanceof Utilisateur,
        ]);
        $formulaireCommentaire->handleRequest($request);

        if ($formulaireCommentaire->isSubmitted() && $formulaireCommentaire->isValid()) {
            $auteur = $this->getUser();
            if (!$auteur instanceof Utilisateur) {
                throw $this->createAccessDeniedException('Connecte-toi pour publier un commentaire.');
            }

            $commentaire->setJeu($jeu)->setAuteur($auteur);
            $entityManager->persist($commentaire);
            $recompense = $progression->recompenseCommentaire($auteur, 'jeu:'.$jeu->getId().':'.hash('sha256', mb_strtolower(trim($commentaire->getContenu()))));
            $entityManager->flush();
            $this->addFlash('success', 'Ton commentaire a été publié.'.($recompense ? ' +10 XP et +5 points.' : ''));
            $this->verifierEtAnnoncerSucces($auteur, $gestionSucces);

            return $this->redirect($this->generateUrl('app_jeu_show', [
                'slug' => $jeu->getSlug(),
                'id' => $jeu->getId(),
            ]).'#commentaires');
        }

        $avisUtilisateur = $this->getUser() instanceof Utilisateur
            ? $avisRepository->findOneBy(['jeu' => $jeu, 'auteur' => $this->getUser()])
            : null;
        $formulaireNote = $this->createForm(NoteJeuType::class, [
            'note' => $avisUtilisateur?->getNote(),
            'contenu' => $avisUtilisateur?->getContenu(),
        ], [
            'action' => $this->generateUrl('app_jeu_noter', ['id' => $jeu->getId()]),
        ]);

        $jeuxSimilaires = $jeuRepository->trouverSimilaires($jeu);
        $commentairesParPage = 10;
        $totalCommentairesRacines = $commentaireJeuRepository->compterRacinesPourJeu($jeu);
        $pagesCommentaires = max(1, (int) ceil($totalCommentairesRacines / $commentairesParPage));
        $pageCommentaires = min(
            max(1, $request->query->getInt('commentaires_page', 1)),
            $pagesCommentaires,
        );

        $modesPresentation = ['complete', 'vertical', 'blocs'];
        $modeEnregistre = match ($jeu->getTypePresentation()) {
            'sections' => 'vertical',
            'sections_blocs' => 'blocs',
            default => 'complete',
        };
        $modeDemande = $request->query->getString('presentation');
        $modeMemorise = $request->cookies->getString('gw_presentation_jeu');
        $modePresentation = in_array($modeDemande, $modesPresentation, true)
            ? $modeDemande
            : (in_array($modeMemorise, $modesPresentation, true) ? $modeMemorise : $modeEnregistre);

        $reponse = $this->render('jeu/show.html.twig', [
            'jeu' => $jeu,
            'auteurFiche' => $auteurFiche,
            'jeuxSimilaires' => $jeuxSimilaires,
            'notesSimilaires' => $avisRepository->trouverResumesPour($jeuxSimilaires),
            'jeuPrecedent' => $jeuRepository->trouverPrecedent($jeu),
            'jeuSuivant' => $jeuRepository->trouverSuivant($jeu),
            'resumeAvis' => $avisRepository->trouverResume($jeu),
            'avisPublies' => $avisRepository->trouverAvisPourJeu($jeu),
            'commentaires' => $commentaireJeuRepository->trouverRecents(
                $jeu,
                $commentairesParPage,
                ($pageCommentaires - 1) * $commentairesParPage,
            ),
            'totalCommentaires' => $commentaireJeuRepository->compterPourJeu($jeu),
            'pageCommentaires' => $pageCommentaires,
            'pagesCommentaires' => $pagesCommentaires,
            'formulaireCommentaire' => $formulaireCommentaire,
            'formulaireNote' => $formulaireNote,
            'avisUtilisateur' => $avisUtilisateur,
            'actualitesLiees' => $actualiteRepository->trouverPourJeu($jeu),
            'modePresentation' => $modePresentation,
        ]);

        if (in_array($modeDemande, $modesPresentation, true) && $modeDemande !== $modeMemorise) {
            $reponse->headers->setCookie(
                Cookie::create('gw_presentation_jeu')
                    ->withValue($modeDemande)
                    ->withExpires(new \DateTimeImmutable('+1 year'))
                    ->withSecure($request->isSecure())
                    ->withHttpOnly(true)
                    ->withSameSite(Cookie::SAMESITE_LAX),
            );
        }

        return $reponse;
    }
}
