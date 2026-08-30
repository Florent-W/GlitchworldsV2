<?php

namespace App\Controller;

use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Form\JeuPropositionType;
use App\Repository\JeuRepository;
use App\Security\PropositionJeuVoter;
use App\Service\JeuGalerieUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PropositionJeuController extends AbstractController
{
    #[Route('/jeu/proposer', name: 'app_jeu_proposer')]
    public function proposer(
        Request $request,
        SluggerInterface $slugger,
        JeuRepository $jeuRepository,
        EntityManagerInterface $entityManager,
        JeuGalerieUploader $galerieUploader,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $estAdministrateur = $this->isGranted('ROLE_ADMIN');
        $jeu = (new Jeu())->setCreateur($utilisateur)->setStatut($estAdministrateur ? StatutJeu::Approuve : StatutJeu::EnAttente);
        $formulaire = $this->createForm(JeuPropositionType::class, $jeu, [
            'bouton_libelle' => $estAdministrateur ? 'Publier' : 'Envoyer pour validation',
        ]);
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
            $this->enregistrerHabillages($jeu, $formulaire, $galerieUploader);
            $entityManager->flush();
            $this->addFlash('success', $estAdministrateur ? 'Le jeu a été publié.' : 'Ta proposition a été envoyée pour validation.');

            if ($estAdministrateur) {
                return $this->redirectToRoute('app_jeu_show', [
                    'slug' => $jeu->getSlug(),
                    'id' => $jeu->getId(),
                ]);
            }

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('jeu/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => false]);
    }

    #[Route('/jeu/proposition/{id}/modifier', name: 'app_jeu_proposition_modifier')]
    public function modifier(Jeu $jeu, Request $request, EntityManagerInterface $entityManager, JeuGalerieUploader $galerieUploader): Response
    {
        $this->denyAccessUnlessGranted(PropositionJeuVoter::MODIFIER, $jeu);

        $estAdministrateur = $this->isGranted('ROLE_ADMIN');
        $formulaire = $this->createForm(JeuPropositionType::class, $jeu, [
            'bouton_libelle' => $estAdministrateur && $jeu->getStatut() !== StatutJeu::Approuve
                ? 'Publier'
                : 'Enregistrer les modifications',
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            if ($estAdministrateur) {
                $jeu->setStatut(StatutJeu::Approuve);
            }
            $this->enregistrerHabillages($jeu, $formulaire, $galerieUploader);
            $entityManager->flush();
            $this->addFlash('success', 'La fiche a été modifiée.');

            if ($jeu->getStatut() === StatutJeu::Approuve) {
                return $this->redirectToRoute('app_jeu_show', [
                    'slug' => $jeu->getSlug(),
                    'id' => $jeu->getId(),
                ]);
            }

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('jeu/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => true]);
    }

    private function enregistrerHabillages(Jeu $jeu, \Symfony\Component\Form\FormInterface $formulaire, JeuGalerieUploader $uploader): void
    {
        if (null === $jeu->getId()) {
            return;
        }

        $miniature = $formulaire->get('miniatureFichier')->getData();
        if ($miniature instanceof UploadedFile) {
            $jeu->setMiniature($uploader->enregistrerHabillage($miniature, (int) $jeu->getId(), 'miniature'));
        }

        $banniere = $formulaire->get('banniereFichier')->getData();
        if ($banniere instanceof UploadedFile) {
            $jeu->setBanniere($uploader->enregistrerHabillage($banniere, (int) $jeu->getId(), 'banniere'));
        }
    }
}
