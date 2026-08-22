<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Utilisateur;
use App\Form\MessageType;
use App\Form\NouvelleConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UtilisateurRepository;
use App\Service\PieceJointeMessageUploader;
use App\Service\CentreNotifications;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/messages')]
#[IsGranted('ROLE_USER')]
final class MessagerieController extends AbstractController
{
    use AnnonceSuccesTrait;

    #[Route('', name: 'app_messages', methods: ['GET'])]
    public function index(Request $request, ConversationRepository $repository, MessageRepository $messageRepository): Response
    {
        $utilisateur = $this->utilisateur();
        $recherche = $request->query->getString('recherche');
        $archivees = $request->query->getBoolean('archivees');
        $conversations = $repository->trouverPour($utilisateur, $recherche, $archivees);
        if ([] !== $conversations && '' === $recherche && !$archivees) {
            return $this->redirectToRoute('app_messages_voir', ['id' => $conversations[0]->getId()]);
        }

        return $this->render('messagerie/index.html.twig', ['conversations' => $conversations, 'conversation' => null, 'formulaireMessage' => null, 'recherche' => $recherche, 'archivees' => $archivees, 'nonLus' => array_reduce($conversations, static function (array $nombres, Conversation $item) use ($messageRepository, $utilisateur): array { $nombres[$item->getId()] = $messageRepository->compterNonLus($utilisateur, $item); return $nombres; }, []), 'totalNonLus' => $messageRepository->compterNonLus($utilisateur)]);
    }

    #[Route('/nouveau', name: 'app_messages_nouveau')]
    public function nouveau(Request $request, ConversationRepository $repository, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager, PieceJointeMessageUploader $uploader, CentreNotifications $notifications, GestionSucces $gestionSucces): Response
    {
        $donnees = [];
        $destinataireId = $request->query->getInt('destinataire');
        if ($destinataireId > 0) {
            $donnees['destinataire'] = $utilisateurRepository->find($destinataireId);
        }
        $formulaire = $this->createForm(NouvelleConversationType::class, $donnees);
        $formulaire->handleRequest($request);
        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $utilisateur = $this->utilisateur();
            $destinataire = $formulaire->get('destinataire')->getData();
            if (!$destinataire instanceof Utilisateur || $destinataire === $utilisateur) {
                $this->addFlash('danger', 'Choisis un autre membre comme destinataire.');
            } else {
                $conversation = $repository->trouverEntre($utilisateur, $destinataire) ?? (new Conversation())->setMembreA($utilisateur)->setMembreB($destinataire);
                $message = (new Message())->setConversation($conversation)->setAuteur($utilisateur)->setContenu((string) $formulaire->get('contenu')->getData());
                $conversation->actualiser();
                $entityManager->persist($conversation);
                $entityManager->flush();
                $fichier = $formulaire->get('fichier')->getData();
                if ($fichier instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) { $message->setPieceJointe($uploader->enregistrer($fichier, (int) $conversation->getId())); }
                $entityManager->persist($message);
                $notifications->ajouter($destinataire, 'Nouveau message', $utilisateur->getPseudo().' t’a envoyé un message.', 'envelope-fill', '/messages/'.$conversation->getId());
                $entityManager->flush();
                $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);

                return $this->redirectToRoute('app_messages_voir', ['id' => $conversation->getId()]);
            }
        }

        return $this->render('messagerie/nouveau.html.twig', ['formulaire' => $formulaire]);
    }

    #[Route('/{id}', name: 'app_messages_voir')]
    public function voir(Conversation $conversation, Request $request, ConversationRepository $repository, MessageRepository $messageRepository, EntityManagerInterface $entityManager, PieceJointeMessageUploader $uploader, CentreNotifications $notifications, GestionSucces $gestionSucces): Response
    {
        $utilisateur = $this->utilisateur();
        if (!$conversation->contient($utilisateur)) {
            throw $this->createAccessDeniedException('Cette conversation ne t’appartient pas.');
        }
        $messageRepository->marquerCommeLus($conversation, $utilisateur);

        $message = (new Message())->setConversation($conversation)->setAuteur($utilisateur);
        $formulaire = $this->createForm(MessageType::class, $message);
        $formulaire->handleRequest($request);
        if ($formulaire->isSubmitted() && $formulaire->isValid()) {
            $conversation->actualiser();
            $fichier = $formulaire->get('fichier')->getData();
            if ($fichier instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) { $message->setPieceJointe($uploader->enregistrer($fichier, (int) $conversation->getId())); }
            $entityManager->persist($message);
            if ($destinataire = $conversation->autreMembre($utilisateur)) { $notifications->ajouter($destinataire, 'Nouveau message', $utilisateur->getPseudo().' t’a envoyé un message.', 'envelope-fill', '/messages/'.$conversation->getId()); }
            $entityManager->flush();
            $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);

            return $this->redirectToRoute('app_messages_voir', ['id' => $conversation->getId()]);
        }

        return $this->render('messagerie/index.html.twig', [
            'conversations' => $repository->trouverPour($utilisateur),
            'conversation' => $conversation,
            'autreMembre' => $conversation->autreMembre($utilisateur),
            'formulaireMessage' => $formulaire,
            'recherche' => '',
            'archivees' => false,
            'nonLus' => array_reduce($repository->trouverPour($utilisateur), static function (array $nombres, Conversation $item) use ($messageRepository, $utilisateur): array { $nombres[$item->getId()] = $messageRepository->compterNonLus($utilisateur, $item); return $nombres; }, []),
            'totalNonLus' => $messageRepository->compterNonLus($utilisateur),
        ]);
    }

    #[Route('/{id}/archiver', name: 'app_messages_archiver', methods: ['POST'])]
    public function archiver(Conversation $conversation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->utilisateur();
        if (!$conversation->contient($utilisateur)) { throw $this->createAccessDeniedException(); }
        if (!$this->isCsrfTokenValid('archiver-conversation-'.$conversation->getId(), (string) $request->request->get('_token'))) { throw $this->createAccessDeniedException('Jeton CSRF invalide.'); }
        $conversation->basculerArchive($utilisateur);
        $entityManager->flush();

        return $this->redirectToRoute('app_messages', $conversation->estArchiveePar($utilisateur) ? ['archivees' => 1] : []);
    }

    #[Route('/{conversation}/piece-jointe/{message}', name: 'app_messages_piece_jointe', methods: ['GET'])]
    public function pieceJointe(Conversation $conversation, Message $message, PieceJointeMessageUploader $uploader): BinaryFileResponse
    {
        if (!$conversation->contient($this->utilisateur()) || $message->getConversation() !== $conversation || null === $message->getPieceJointe()) {
            throw $this->createNotFoundException();
        }
        $chemin = $uploader->chemin((int) $conversation->getId(), $message->getPieceJointe());
        if (!is_file($chemin)) { throw $this->createNotFoundException('Pièce jointe introuvable.'); }

        return $this->file($chemin, $message->getPieceJointe());
    }

    private function utilisateur(): Utilisateur
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }

        return $utilisateur;
    }
}
