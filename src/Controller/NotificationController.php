<?php
namespace App\Controller;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/notifications')]
final class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notifications', methods: ['GET'])] public function index(NotificationRepository $repository): Response { return $this->render('notification/index.html.twig', ['notifications' => $repository->trouverPour($this->membre()), 'nonLues' => $repository->compterNonLues($this->membre())]); }
    #[Route('/{id}/lire', name: 'app_notification_lire', requirements: ['id' => '\d+'], methods: ['POST'])] public function lire(Notification $notification, Request $request, EntityManagerInterface $em): Response { if ($notification->getUtilisateur() !== $this->membre() || !$this->isCsrfTokenValid('lire-notification-'.$notification->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); } $notification->marquerLue(); $em->flush(); return $notification->getUrl() ? $this->redirect($notification->getUrl()) : $this->redirectToRoute('app_notifications'); }
    #[Route('/tout-lire', name: 'app_notifications_tout_lire', methods: ['POST'])] public function toutLire(Request $request, NotificationRepository $repository, EntityManagerInterface $em): Response { $membre = $this->membre(); if (!$this->isCsrfTokenValid('notifications-tout-lire', $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); } foreach ($repository->findBy(['utilisateur' => $membre, 'lue' => false]) as $notification) { $notification->marquerLue(); } $em->flush(); return $this->redirectToRoute('app_notifications'); }
    private function membre(): Utilisateur { $membre = $this->getUser(); if (!$membre instanceof Utilisateur) { throw $this->createAccessDeniedException(); } return $membre; }
}
