<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\Utilisateur;
use App\Entity\ReponsePublication;
use App\Entity\VotePublication;
use App\Form\PublicationType;
use App\Security\PublicationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ProgressionUtilisateur;
use App\Service\GestionSucces;
use App\Service\ImagePublicationUploader;
use App\Service\CentreNotifications;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PublicationController extends AbstractController
{
    use AnnonceSuccesTrait;
    #[Route('/communaute/publication', name: 'app_publication_creer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function creer(Request $request, EntityManagerInterface $entityManager, ProgressionUtilisateur $progression, GestionSucces $gestionSucces, ImagePublicationUploader $uploader): Response
    {
        $publication = new Publication();
        $formulaire = $this->createForm(PublicationType::class, $publication);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $options = $this->optionsSondage($formulaire->get('optionsSondageTexte')->getData());
            if ($publication->getQuestionSondage() !== null && count($options) < 2) { $this->addFlash('danger', 'Un sondage doit proposer au moins deux choix.'); return $this->redirect($this->generateUrl('app_communaute').'#fil'); }
            $publication->setOptionsSondage($options);
            $utilisateur = $this->getUser();
            if ($utilisateur instanceof Utilisateur) {
                $publication->setAuteur($utilisateur);
                $recompenseAccordee = $progression->recompensePublication($utilisateur, hash('sha256', mb_strtolower(trim($publication->getContenu()))));
            }
            $entityManager->persist($publication);
            $entityManager->flush();
            $image = $formulaire->get('imageFichier')->getData();
            if ($image instanceof UploadedFile) { $publication->setImage($uploader->enregistrer($image, (int) $publication->getId())); $entityManager->flush(); }
            $this->addFlash('success', 'Ta publication est en ligne.'.(($recompenseAccordee ?? false) ? ' +20 XP et +10 points.' : ''));
            if ($utilisateur instanceof Utilisateur) {
                $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
            }
        } else {
            $erreurs = [];
            foreach ($formulaire->getErrors(true) as $erreur) { $erreurs[] = $erreur->getMessage(); }
            $this->addFlash('danger', $erreurs ? implode(' ', array_unique($erreurs)) : 'La publication doit contenir entre 3 et 1 000 caractères.');
        }

        return $this->redirect($this->generateUrl('app_communaute').'#fil');
    }

    #[Route('/communaute/publication/{id}/modifier', name: 'app_publication_modifier')]
    public function modifier(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(PublicationVoter::MODIFIER, $publication);
        $formulaire = $this->createForm(PublicationType::class, $publication);
        $formulaire->handleRequest($request);

        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Ta publication a été modifiée.');

            return $this->redirect($this->generateUrl('app_communaute').'#fil');
        }

        return $this->render('communaute/modifier_publication.html.twig', [
            'publication' => $publication,
            'formulaire' => $formulaire,
        ]);
    }

    #[Route('/communaute/publication/{id}/supprimer', name: 'app_publication_supprimer', methods: ['POST'])]
    public function supprimer(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(PublicationVoter::SUPPRIMER, $publication);
        if (!$this->isCsrfTokenValid('supprimer-publication-'.$publication->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $entityManager->remove($publication);
        $entityManager->flush();
        $this->addFlash('success', 'La publication a été supprimée.');

        return $this->redirect($this->generateUrl('app_communaute').'#fil');
    }

    #[Route('/communaute/publication/{id}/aimer', name: 'app_publication_aimer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function aimer(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('aimer-publication-'.$publication->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        $utilisateur = $this->getUser();
        if ($utilisateur instanceof Utilisateur) {
            $publication->estAimePar($utilisateur) ? $publication->retirerAime($utilisateur) : $publication->ajouterAime($utilisateur);
            $entityManager->flush();
        }

        return $this->redirect($this->generateUrl('app_communaute').'#publication-'.$publication->getId());
    }

    #[Route('/communaute/publication/{id}/repondre', name: 'app_publication_repondre', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function repondre(Publication $publication, Request $request, EntityManagerInterface $entityManager, CentreNotifications $notifications): Response
    {
        if (!$this->isCsrfTokenValid('repondre-publication-'.$publication->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $utilisateur = $this->getUser(); $contenu = trim($request->request->getString('contenu'));
        if (!$utilisateur instanceof Utilisateur || mb_strlen($contenu) < 2 || mb_strlen($contenu) > 600) { $this->addFlash('danger', 'La réponse doit contenir entre 2 et 600 caractères.'); return $this->redirect($this->generateUrl('app_communaute').'#publication-'.$publication->getId()); }
        $entityManager->persist((new ReponsePublication())->setPublication($publication)->setAuteur($utilisateur)->setContenu($contenu));
        if ($publication->getAuteur() && $publication->getAuteur() !== $utilisateur) { $notifications->ajouter($publication->getAuteur(), 'Nouvelle réponse', $utilisateur->getPseudo().' a répondu à ta publication.', 'reply-fill', '/communaute#publication-'.$publication->getId()); }
        $entityManager->flush(); return $this->redirect($this->generateUrl('app_communaute').'#publication-'.$publication->getId());
    }

    #[Route('/communaute/publication/{id}/voter', name: 'app_publication_voter', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function voter(Publication $publication, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser(); if (!$utilisateur instanceof Utilisateur || !$this->isCsrfTokenValid('voter-publication-'.$publication->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        $option = $request->request->getInt('option', -1); if (!$publication->isSondage() || !array_key_exists($option, $publication->getOptionsSondage())) { throw $this->createNotFoundException(); }
        $vote = $entityManager->getRepository(VotePublication::class)->findOneBy(['publication' => $publication, 'utilisateur' => $utilisateur]) ?? (new VotePublication())->setPublication($publication)->setUtilisateur($utilisateur);
        $vote->setOptionChoisie($option); $entityManager->persist($vote); $entityManager->flush(); return $this->redirect($this->generateUrl('app_communaute').'#publication-'.$publication->getId());
    }

    private function optionsSondage(mixed $valeur): array { return array_slice(array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/', (string) $valeur) ?: [])))), 0, 6); }
}
