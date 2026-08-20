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
use Symfony\Component\Form\FormError;
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

        $jeu = (new Jeu())->setCreateur($utilisateur)->setStatut(StatutJeu::EnAttente);
        $formulaire = $this->createForm(JeuPropositionType::class, $jeu);
        $formulaire->handleRequest($request);
        $this->verifierLimiteGalerie($jeu, $formulaire, []);

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
            $this->enregistrerGalerie($jeu, $formulaire->get('imagesGalerie')->getData(), $galerieUploader);
            $this->enregistrerHabillages($jeu, $formulaire, $galerieUploader);
            $entityManager->flush();
            $this->addFlash('success', 'Ta proposition a été envoyée pour validation.');

            return $this->redirectToRoute('app_compte');
        }

        return $this->render('jeu/proposer.html.twig', ['formulaire' => $formulaire, 'modification' => false]);
    }

    #[Route('/jeu/proposition/{id}/modifier', name: 'app_jeu_proposition_modifier')]
    public function modifier(Jeu $jeu, Request $request, EntityManagerInterface $entityManager, JeuGalerieUploader $galerieUploader): Response
    {
        $this->denyAccessUnlessGranted(PropositionJeuVoter::MODIFIER, $jeu);

        $formulaire = $this->createForm(JeuPropositionType::class, $jeu, [
            'bouton_libelle' => 'Enregistrer les modifications',
        ]);
        $formulaire->handleRequest($request);
        $aSupprimer = $request->request->all('supprimer_galerie');
        $this->verifierLimiteGalerie($jeu, $formulaire, $aSupprimer);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            foreach ($aSupprimer as $nom) {
                if (in_array($nom, $jeu->getGalerie(), true)) {
                    $galerieUploader->supprimer($nom, (int) $jeu->getId());
                    $jeu->removeImageGalerie($nom);
                }
            }
            $this->enregistrerGalerie($jeu, $formulaire->get('imagesGalerie')->getData(), $galerieUploader);
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

    /** @param list<UploadedFile> $images */
    private function enregistrerGalerie(Jeu $jeu, array $images, JeuGalerieUploader $uploader): void
    {
        $placesRestantes = max(0, 8 - count($jeu->getGalerie()));
        foreach (array_slice($images, 0, $placesRestantes) as $image) {
            $jeu->addImageGalerie($uploader->enregistrer($image, (int) $jeu->getId()));
        }
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

    /** @param list<string> $aSupprimer */
    private function verifierLimiteGalerie(Jeu $jeu, \Symfony\Component\Form\FormInterface $formulaire, array $aSupprimer): void
    {
        if (!$formulaire->isSubmitted()) {
            return;
        }

        $suppressionsValides = count(array_intersect($jeu->getGalerie(), $aSupprimer));
        $nouveauxFichiers = $formulaire->get('imagesGalerie')->getData();
        if (count($jeu->getGalerie()) - $suppressionsValides + count($nouveauxFichiers) > 8) {
            $formulaire->get('imagesGalerie')->addError(new FormError('La galerie peut contenir 8 images maximum.'));
        }
    }
}
