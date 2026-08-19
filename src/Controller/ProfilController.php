<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\JeuRepository;
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
        AchatBoutiqueRepository $achats,
        SuccesRepository $succesRepository,
        SuccesUtilisateurRepository $deblocages,
    ): Response {
        $section = $request->query->getString('section', 'apropos');
        if (!in_array($section, ['apropos', 'activite', 'jeux', 'favoris', 'succes'], true)) {
            $section = 'apropos';
        }

        $succesAcquis = $deblocages->trouverPour($membre);

        return $this->render('profil/voir.html.twig', [
            'membre' => $membre,
            'section' => $section,
            'publications' => $publications->trouverPourAuteur($membre),
            'jeux' => $jeux->trouverApprouvesPar($membre),
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
}
