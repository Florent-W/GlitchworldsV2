<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Enum\StatutActualite;
use App\Form\ActualiteType;
use App\Repository\ActualiteRepository;
use App\Service\ActualiteImageUploader;
use App\Service\NotificationAbonnes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/administration/actualites', name: 'app_administration_actualites_')]
final class AdministrationActualiteController extends AbstractController
{
    #[Route('', name: 'liste', methods: ['GET'])]
    public function liste(): Response
    {
        return $this->redirectToRoute('app_moderation_actualites');
    }

    #[Route('/creer', name: 'creer')]
    public function creer(Request $request, SluggerInterface $slugger, ActualiteRepository $actualiteRepository, ActualiteImageUploader $imageUploader, EntityManagerInterface $entityManager, NotificationAbonnes $notificationAbonnes): Response
    {
        $actualite = new Actualite();
        $formulaire = $this->createForm(ActualiteType::class, $actualite, ['bouton_libelle' => 'Créer l’actualité']);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $actualite->setSlug($this->creerSlugUnique($actualite->getTitre(), $slugger, $actualiteRepository));
            $auteur = $this->getUser();
            if ($auteur instanceof Utilisateur) {
                $actualite->setAuteur($auteur);
            }
            $entityManager->persist($actualite);
            $entityManager->flush();
            $this->enregistrerHabillages($actualite, $formulaire, $imageUploader, $entityManager);
            if ($actualite->getStatut() === StatutActualite::Publiee) {
                $notificationAbonnes->notifierActualite($actualite);
                $entityManager->flush();
            }
            $this->addFlash('success', 'L’actualité a été créée.');

            return $this->redirectToRoute('app_moderation_actualites');
        }

        return $this->render('administration/actualite/formulaire.html.twig', ['formulaire' => $formulaire, 'titre' => 'Créer une actualité']);
    }

    #[Route('/{id}/modifier', name: 'modifier', requirements: ['id' => '\d+'])]
    public function modifier(Actualite $actualite, Request $request, ActualiteImageUploader $imageUploader, EntityManagerInterface $entityManager): Response
    {
        $ancienStatut = $actualite->getStatut();
        $formulaire = $this->createForm(ActualiteType::class, $actualite);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $this->enregistrerHabillages($actualite, $formulaire, $imageUploader, $entityManager);
            if ($ancienStatut !== StatutActualite::Publiee && $actualite->getStatut() === StatutActualite::Publiee) {
                $actualite->setPublieeLe(new \DateTimeImmutable());
            }
            $entityManager->flush();
            $this->addFlash('success', 'L’actualité a été modifiée.');

            return $this->redirectToRoute('app_moderation_actualites');
        }

        return $this->render('administration/actualite/formulaire.html.twig', ['formulaire' => $formulaire, 'titre' => 'Modifier l’actualité']);
    }

    #[Route('/{id}/supprimer', name: 'supprimer', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function supprimer(Actualite $actualite, Request $request, ActualiteImageUploader $imageUploader, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('supprimer-actualite-'.$actualite->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $id = (int) $actualite->getId();
        $entityManager->remove($actualite);
        $entityManager->flush();
        $imageUploader->supprimerImages($id);
        $this->addFlash('success', 'L’actualité et ses images ont été supprimées.');

        return $this->redirectToRoute('app_moderation_actualites');
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
}
