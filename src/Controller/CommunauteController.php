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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommunauteController extends AbstractController
{
    #[Route('/communaute', name: 'app_communaute', methods: ['GET'])]
    public function index(
        CommentaireJeuRepository $commentaireJeuRepository,
        CommentaireActualiteRepository $commentaireActualiteRepository,
        JeuRepository $jeuRepository,
        ActualiteRepository $actualiteRepository,
        UtilisateurRepository $utilisateurRepository,
        PublicationRepository $publicationRepository,
    ): Response {
        return $this->render('communaute/index.html.twig', [
            'commentairesJeux' => $commentaireJeuRepository->trouverDerniersPublics(),
            'commentairesActualites' => $commentaireActualiteRepository->trouverDerniersPublics(),
            'nouveauxJeux' => $jeuRepository->trouverNouveautes(4),
            'dernieresActualites' => $actualiteRepository->trouverDernieres(4),
            'statistiques' => [
                'membres' => $utilisateurRepository->count([]),
                'jeux' => $jeuRepository->count([]),
                'commentaires' => $commentaireJeuRepository->count([]) + $commentaireActualiteRepository->count([]),
            ],
            'publications' => $publicationRepository->trouverDernieres(),
            'formulairePublication' => $this->getUser() ? $this->createForm(PublicationType::class, new Publication(), [
                'action' => $this->generateUrl('app_publication_creer'),
            ])->createView() : null,
        ]);
    }
}
