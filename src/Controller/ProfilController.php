<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\BiographieProfilType;
use App\Repository\AvisRepository;
use App\Repository\JeuRepository;
use App\Repository\ActualiteRepository;
use App\Repository\ListeJeuxRepository;
use App\Repository\PublicationRepository;
use App\Repository\AchatBoutiqueRepository;
use App\Repository\SuccesRepository;
use App\Repository\SuccesUtilisateurRepository;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfilController extends AbstractController
{
    use AnnonceSuccesTrait;

    #[Route('/membre/{id}', name: 'app_profil', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function voir(
        Utilisateur $membre,
        Request $request,
        PublicationRepository $publications,
        JeuRepository $jeux,
        ActualiteRepository $actualites,
        ListeJeuxRepository $listesJeux,
        AchatBoutiqueRepository $achats,
        SuccesRepository $succesRepository,
        SuccesUtilisateurRepository $deblocages,
        AvisRepository $avisRepository,
    ): Response {
        $section = $request->query->getString('section', 'apropos');
        if (!in_array($section, ['apropos', 'activite', 'jeux', 'actualites', 'listes', 'favoris', 'succes'], true)) {
            $section = 'apropos';
        }

        $listes = $listesJeux->trouverPour($membre);
        $jeuxDesListes = [];
        foreach ($listes as $liste) {
            foreach ($liste->getJeux() as $jeu) {
                $jeuxDesListes[] = $jeu;
            }
        }

        $succesAcquis = $deblocages->trouverPour($membre);
        $nombreJeuxPublies = $jeux->compterApprouvesPar($membre);
        $jeuxFavoris = $membre->getJeuxFavoris()->toArray();
        $jeuxPagination = $section === 'jeux'
            ? $jeux->trouverApprouvesParPagines($membre, max(1, (int) $request->query->get('page', 1)))
            : ['jeux' => [], 'total' => $nombreJeuxPublies, 'page' => 1, 'pages' => 1, 'parPage' => 12];
        $jeuxPublies = $jeuxPagination['jeux'];
        $nombreActualitesPubliees = $actualites->compterPublieesPar($membre);
        $actualitesPagination = $section === 'actualites'
            ? $actualites->trouverPublieesParPagines($membre, max(1, (int) $request->query->get('page', 1)))
            : ['actualites' => [], 'total' => $nombreActualitesPubliees, 'page' => 1, 'pages' => 1, 'parPage' => 12];

        $formulaireBiographie = null;
        $utilisateur = $this->getUser();
        if ($utilisateur instanceof Utilisateur && $utilisateur === $membre) {
            $formulaireBiographie = $this->createForm(BiographieProfilType::class, $membre)->createView();
        }

        return $this->render('profil/voir.html.twig', [
            'membre' => $membre,
            'section' => $section,
            'formulaireBiographie' => $formulaireBiographie,
            'publications' => $publications->trouverPourAuteur($membre),
            'nombreJeuxPublies' => $nombreJeuxPublies,
            'jeux' => $jeuxPublies,
            'jeuxPagination' => $jeuxPagination,
            'nombreActualitesPubliees' => $nombreActualitesPubliees,
            'actualitesPubliees' => $actualitesPagination['actualites'],
            'actualitesPagination' => $actualitesPagination,
            'listes' => $listes,
            'notesJeux' => $avisRepository->trouverResumesPour([...$jeuxPublies, ...$jeuxFavoris, ...$jeuxDesListes]),
            'achatsBoutique' => $achats->trouverPourUtilisateur($membre),
            'succes' => $succesRepository->findAll(),
            'succesAcquis' => $succesAcquis,
            'codesAcquis' => array_map(static fn ($d) => $d->getSucces()?->getCode(), $succesAcquis),
        ]);
    }

    #[Route('/membre/{id}/suivre', name: 'app_profil_suivre', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function suivre(Utilisateur $membre, Request $request, EntityManagerInterface $entityManager, GestionSucces $gestionSucces): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        if ($utilisateur === $membre) {
            throw $this->createAccessDeniedException('Tu ne peux pas suivre ton propre profil.');
        }
        if (!$this->isCsrfTokenValid('suivre-'.$membre->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $suitDeja = $utilisateur->suit($membre);
        $utilisateur->suit($membre) ? $utilisateur->nePlusSuivre($membre) : $utilisateur->suivre($membre);
        $entityManager->flush();

        if (!$suitDeja && $utilisateur->suit($membre)) {
            $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
        }

        return $this->redirectToRoute('app_profil', ['id' => $membre->getId()]);
    }

    #[Route('/membre/{id}/biographie', name: 'app_profil_biographie', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function modifierBiographie(Utilisateur $membre, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur || $utilisateur !== $membre) {
            throw $this->createAccessDeniedException();
        }

        $formulaire = $this->createForm(BiographieProfilType::class, $membre);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ta présentation a été mise à jour.');
        }

        return $this->redirectToRoute('app_profil', ['id' => $membre->getId(), 'section' => 'apropos']);
    }
}
