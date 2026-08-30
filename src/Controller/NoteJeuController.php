<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Form\NoteJeuType;
use App\Repository\AvisRepository;
use App\Service\ProgressionUtilisateur;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NoteJeuController extends AbstractController
{
    use AnnonceSuccesTrait;
    #[Route('/jeu/{id}/noter', name: 'app_jeu_noter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function noter(
        Jeu $jeu,
        Request $request,
        AvisRepository $avisRepository,
        EntityManagerInterface $entityManager,
        ProgressionUtilisateur $progression,
        GestionSucces $gestionSucces,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        if ($jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException('Ce jeu n’existe pas.');
        }

        $avis = $avisRepository->findOneBy(['jeu' => $jeu, 'auteur' => $utilisateur]) ?? (new Avis())
            ->setJeu($jeu)
            ->setAuteur($utilisateur);
        $formulaire = $this->createForm(NoteJeuType::class, [
            'note' => $avis->getNote(),
            'contenu' => $avis->getContenu(),
        ], [
            'action' => $this->generateUrl('app_jeu_noter', ['id' => $jeu->getId()]),
        ]);
        $formulaire->handleRequest($request);

        if (!$formulaire->isSubmitted() || !$formulaire->isValid()) {
            $this->addFlash('danger', 'Vérifie la note et le texte de ton avis.');
        } else {
            $avis->setNote((float) $formulaire->get('note')->getData());
            $avis->setContenu((string) $formulaire->get('contenu')->getData());
            $avis->setDateAvis(new \DateTimeImmutable());
            $entityManager->persist($avis);
            $recompense = $progression->recompenseNote($utilisateur, (int) $jeu->getId());
            $entityManager->flush();
            $message = $avis->getContenu() !== '' ? 'Ta note et ton avis ont été enregistrés.' : 'Ta note a été enregistrée.';
            $this->addFlash('success', $message.($recompense ? ' +5 XP et +2 points.' : ''));
            $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
        }

        return $this->redirect($this->generateUrl('app_jeu_show', [
            'slug' => $jeu->getSlug(),
            'id' => $jeu->getId(),
        ]).'#avis-joueurs');
    }
}
