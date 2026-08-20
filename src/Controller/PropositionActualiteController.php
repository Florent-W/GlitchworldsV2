<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Enum\StatutActualite;
use App\Form\ActualitePropositionType;
use App\Repository\ActualiteRepository;
use App\Security\PropositionActualiteVoter;
use App\Service\ActualiteImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class PropositionActualiteController extends AbstractController
{
    #[Route('/actualite/proposer', name: 'app_actualite_proposer')]
    public function proposer(
        Request $request,
        SluggerInterface $slugger,
        ActualiteRepository $actualiteRepository,
        ActualiteImageUploader $imageUploader,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $actualite = (new Actualite())
            ->setAuteur($utilisateur)
            ->setStatut(StatutActualite::EnAttente);
        $formulaire = $this->createForm(ActualitePropositionType::class, $actualite);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $actualite->setSlug($this->creerSlugUnique($actualite->getTitre(), $slugger, $actualiteRepository));
            $entityManager->persist($actualite);
            $entityManager->flush();
            $this->enregistrerHabillages($actualite, $formulaire, $imageUploader, $entityManager);
            $this->addFlash('success', 'Ton actualité a été envoyée pour validation.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('actualite/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => false]);
    }

    #[Route('/actualite/proposition/{id}/modifier', name: 'app_actualite_proposition_modifier', requirements: ['id' => '\d+'])]
    public function modifier(
        Actualite $actualite,
        Request $request,
        ActualiteImageUploader $imageUploader,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(PropositionActualiteVoter::MODIFIER, $actualite);

        $formulaire = $this->createForm(ActualitePropositionType::class, $actualite, [
            'bouton_libelle' => 'Enregistrer les modifications',
        ]);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $this->enregistrerHabillages($actualite, $formulaire, $imageUploader, $entityManager);
            $entityManager->flush();
            $this->addFlash('success', 'L’actualité a été modifiée.');

            if ($actualite->getStatut() === StatutActualite::Publiee) {
                return $this->redirectToRoute('app_actualite_voir', [
                    'slug' => $actualite->getSlug(),
                    'id' => $actualite->getId(),
                ]);
            }

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('actualite/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => true]);
    }

    private function enregistrerHabillages(
        Actualite $actualite,
        FormInterface $formulaire,
        ActualiteImageUploader $imageUploader,
        EntityManagerInterface $entityManager,
    ): void {
        if (null === $actualite->getId()) {
            return;
        }

        $banniere = $formulaire->get('banniereFichier')->getData();
        if ($banniere instanceof UploadedFile) {
            $actualite->setBanniere($imageUploader->enregistrer($banniere, (int) $actualite->getId(), 'banniere'));
        }

        $miniature = $formulaire->get('miniatureFichier')->getData();
        if ($miniature instanceof UploadedFile) {
            $actualite->setMiniature($imageUploader->enregistrer($miniature, (int) $actualite->getId(), 'miniature'));
        }

        if ($banniere instanceof UploadedFile || $miniature instanceof UploadedFile) {
            $entityManager->flush();
        }
    }

    private function creerSlugUnique(string $titre, SluggerInterface $slugger, ActualiteRepository $actualiteRepository): string
    {
        $base = strtolower($slugger->slug($titre)->toString()) ?: 'actualite';
        $slug = $base;
        $suffixe = 2;
        while ($actualiteRepository->findOneBy(['slug' => $slug]) !== null) {
            $slug = $base.'-'.$suffixe++;
        }

        return $slug;
    }
}
