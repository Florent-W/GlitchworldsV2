<?php

namespace App\Controller;

use App\Entity\CommentaireActualite;
use App\Entity\CommentaireJeu;
use App\Entity\Jeu;
use App\Entity\Publication;
use App\Entity\Avis;
use App\Entity\Message;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Form\SignalementType;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\PublicationRepository;
use App\Repository\AvisRepository;
use App\Repository\MessageRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SignalementController extends AbstractController
{
    #[Route('/signaler/{type}/{id}', name: 'app_signaler', requirements: ['type' => 'jeu|commentaire-jeu|commentaire-actualite|publication|profil|avis|message', 'id' => '\d+'])]
    public function signaler(
        string $type,
        int $id,
        Request $request,
        JeuRepository $jeux,
        CommentaireJeuRepository $commentairesJeux,
        CommentaireActualiteRepository $commentairesActualites,
        PublicationRepository $publications,
        UtilisateurRepository $utilisateurs,
        AvisRepository $avis,
        MessageRepository $messages,
        SignalementRepository $signalements,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }

        $cible = match ($type) {
            'jeu' => $jeux->find($id),
            'commentaire-jeu' => $commentairesJeux->find($id),
            'commentaire-actualite' => $commentairesActualites->find($id),
            'publication' => $publications->find($id),
            'profil' => $utilisateurs->find($id),
            'avis' => $avis->find($id),
            'message' => $messages->find($id),
        };
        if (!$cible) { throw $this->createNotFoundException('Le contenu à signaler n’existe plus.'); }

        if ($cible instanceof Utilisateur && $cible === $utilisateur) {
            throw $this->createAccessDeniedException('Tu ne peux pas signaler ton propre profil.');
        }
        if ($cible instanceof Avis && $cible->getAuteur() === $utilisateur) {
            throw $this->createAccessDeniedException('Tu ne peux pas signaler ton propre avis.');
        }
        if ($cible instanceof Message && (!$cible->getConversation()?->contient($utilisateur) || $cible->getAuteur() === $utilisateur)) {
            throw $this->createAccessDeniedException('Tu ne peux signaler que les messages que tu as reçus.');
        }

        $propriete = match ($type) { 'jeu' => 'jeu', 'commentaire-jeu' => 'commentaireJeu', 'commentaire-actualite' => 'commentaireActualite', 'publication' => 'publication', 'profil' => 'profil', 'avis' => 'avis', 'message' => 'message' };
        if ($signalements->findOneBy(['signalePar' => $utilisateur, $propriete => $cible, 'statut' => \App\Enum\StatutSignalement::EnAttente])) {
            $this->addFlash('info', 'Tu as déjà signalé ce contenu. La modération va l’examiner.');
            return $this->redirigerVersCible($cible);
        }

        $signalement = (new Signalement())->setSignalePar($utilisateur);
        match (true) {
            $cible instanceof Jeu => $signalement->setJeu($cible),
            $cible instanceof CommentaireJeu => $signalement->setCommentaireJeu($cible),
            $cible instanceof CommentaireActualite => $signalement->setCommentaireActualite($cible),
            $cible instanceof Publication => $signalement->setPublication($cible),
            $cible instanceof Utilisateur => $signalement->setProfil($cible),
            $cible instanceof Avis => $signalement->setAvis($cible),
            $cible instanceof Message => $signalement->setMessage($cible),
        };
        $formulaire = $this->createForm(SignalementType::class, $signalement);
        $formulaire->handleRequest($request);
        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $entityManager->persist($signalement);
            $entityManager->flush();
            $this->addFlash('success', 'Merci. Ton signalement a été transmis à la modération.');
            return $this->redirigerVersCible($cible);
        }

        return $this->render('signalement/signaler.html.twig', ['formulaire' => $formulaire, 'signalement' => $signalement]);
    }

    private function redirigerVersCible(object $cible): Response
    {
        return match (true) {
            $cible instanceof Jeu => $this->redirectToRoute('app_jeu_show', ['slug' => $cible->getSlug(), 'id' => $cible->getId()]),
            $cible instanceof CommentaireJeu => $this->redirectToRoute('app_jeu_show', ['slug' => $cible->getJeu()?->getSlug(), 'id' => $cible->getJeu()?->getId(), '_fragment' => 'commentaire-'.$cible->getId()]),
            $cible instanceof CommentaireActualite => $this->redirectToRoute('app_actualite_voir', ['slug' => $cible->getActualite()?->getSlug(), 'id' => $cible->getActualite()?->getId(), '_fragment' => 'commentaire-'.$cible->getId()]),
            $cible instanceof Publication => $this->redirectToRoute('app_communaute', ['_fragment' => 'publication-'.$cible->getId()]),
            $cible instanceof Utilisateur => $this->redirectToRoute('app_profil', ['id' => $cible->getId()]),
            $cible instanceof Avis => $this->redirectToRoute('app_jeu_show', ['slug' => $cible->getJeu()?->getSlug(), 'id' => $cible->getJeu()?->getId(), '_fragment' => 'avis-joueurs']),
            $cible instanceof Message => $this->redirectToRoute('app_messages_voir', ['id' => $cible->getConversation()?->getId()]),
        };
    }
}
