<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Form\JeuPropositionType;
use App\Repository\JeuRepository;
use App\Security\PropositionJeuVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class PropositionJeuController extends AbstractController
{
    #[Route('/jeu/proposer', name: 'app_jeu_proposer')]
    public function proposer(
        Request $request,
        SluggerInterface $slugger,
        JeuRepository $jeuRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $jeu = (new Jeu())->setCreateur($utilisateur)->setStatut(StatutJeu::EnAttente);
        $formulaire = $this->createForm(JeuPropositionType::class, $jeu);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $baseSlug = strtolower($slugger->slug((string) $jeu->getNom())->toString());
            $slug = $baseSlug;
            $suffixe = 2;
            while ($jeuRepository->findOneBy(['slug' => $slug]) !== null) {
                $slug = $baseSlug.'-'.$suffixe++;
            }
            $jeu->setSlug($slug);

            $entityManager->persist($jeu);
            $entityManager->flush();
            $this->addFlash('success', 'Ta proposition a été envoyée pour validation.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('jeu/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => false]);
    }

    #[Route('/jeu/proposition/{id}/modifier', name: 'app_jeu_proposition_modifier')]
    public function modifier(Jeu $jeu, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(PropositionJeuVoter::MODIFIER, $jeu);

        $formulaire = $this->createForm(JeuPropositionType::class, $jeu, [
            'bouton_libelle' => 'Enregistrer les modifications',
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ta proposition a été modifiée.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('jeu/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => true]);
    }
}
