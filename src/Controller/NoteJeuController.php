<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Jeu;
use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use App\Form\NoteJeuType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NoteJeuController extends AbstractController
{
    #[Route('/jeu/{id}/noter', name: 'app_jeu_noter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function noter(
        Jeu $jeu,
        Request $request,
        AvisRepository $avisRepository,
        EntityManagerInterface $entityManager,
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
        $formulaire = $this->createForm(NoteJeuType::class, ['note' => $avis->getNote()], [
            'action' => $this->generateUrl('app_jeu_noter', ['id' => $jeu->getId()]),
        ]);
        $formulaire->handleRequest($request);

        if (!$formulaire->isSubmitted() || !$formulaire->isValid()) {
            $this->addFlash('danger', 'La note doit être comprise entre 1 et 5.');
        } else {
            $avis->setNote((float) $formulaire->get('note')->getData());
            $avis->setDateAvis(new \DateTimeImmutable());
            $entityManager->persist($avis);
            $entityManager->flush();
            $this->addFlash('success', 'Ta note a été enregistrée.');
        }

        return $this->redirectToRoute('app_jeu_show', ['slug' => $jeu->getSlug(), 'id' => $jeu->getId()]);
    }
}
