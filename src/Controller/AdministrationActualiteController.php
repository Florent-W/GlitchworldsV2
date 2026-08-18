<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Entity\Utilisateur;
use App\Enum\StatutActualite;
use App\Form\ActualiteType;
use App\Repository\ActualiteRepository;
use App\Service\ActualiteImageUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/administration/actualites', name: 'app_administration_actualites_')]
final class AdministrationActualiteController extends AbstractController
{
    #[Route('', name: 'liste', methods: ['GET'])]
    public function liste(ActualiteRepository $actualiteRepository): Response
    {
        return $this->render('administration/actualite/index.html.twig', [
            'actualites' => $actualiteRepository->findBy([], ['publieeLe' => 'DESC']),
        ]);
    }

    #[Route('/creer', name: 'creer')]
    public function creer(Request $request, SluggerInterface $slugger, ActualiteRepository $actualiteRepository, ActualiteImageUploader $imageUploader, EntityManagerInterface $entityManager): Response
    {
        $actualite = new Actualite();
        $formulaire = $this->createForm(ActualiteType::class, $actualite, ['bouton_libelle' => 'Créer l’actualité']);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $image = $formulaire->get('image')->getData();
            $actualite->setSlug($this->creerSlugUnique($actualite->getTitre(), $slugger, $actualiteRepository));
            $auteur = $this->getUser();
            if ($auteur instanceof Utilisateur) {
                $actualite->setAuteur($auteur);
            }
            $entityManager->persist($actualite);
            $entityManager->flush();
            if ($image instanceof UploadedFile) {
                $actualite->setMiniature($imageUploader->enregistrer($image, (int) $actualite->getId()));
                $entityManager->flush();
            }
            $this->addFlash('success', 'L’actualité a été créée.');

            return $this->redirectToRoute('app_administration_actualites_liste');
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
            $image = $formulaire->get('image')->getData();
            if ($image instanceof UploadedFile) {
                $actualite->setMiniature($imageUploader->enregistrer($image, (int) $actualite->getId()));
            }
            if ($ancienStatut !== StatutActualite::Publiee && $actualite->getStatut() === StatutActualite::Publiee) {
                $actualite->setPublieeLe(new \DateTimeImmutable());
            }
            $entityManager->flush();
            $this->addFlash('success', 'L’actualité a été modifiée.');

            return $this->redirectToRoute('app_administration_actualites_liste');
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

        return $this->redirectToRoute('app_administration_actualites_liste');
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
