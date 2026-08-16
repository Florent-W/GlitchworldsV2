<?php

namespace App\Controller;

use App\Entity\AchatBoutique;
use App\Entity\ArticleBoutique;
use App\Entity\Utilisateur;
use App\Enum\TypeArticleBoutique;
use App\Repository\AchatBoutiqueRepository;
use App\Repository\ArticleBoutiqueRepository;
use App\Service\Boutique;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BoutiqueController extends AbstractController
{
    #[Route('/boutique', name: 'app_boutique', methods: ['GET'])]
    public function index(ArticleBoutiqueRepository $articles, AchatBoutiqueRepository $achats): Response
    {
        $utilisateur = $this->getUser();
        $possessions = $utilisateur instanceof Utilisateur ? $achats->trouverPourUtilisateur($utilisateur) : [];
        return $this->render('boutique/index.html.twig', ['articles' => $articles->trouverActifs(), 'achats' => $possessions, 'articlesPossedes' => array_map(static fn (AchatBoutique $achat) => $achat->getArticle()?->getId(), $possessions)]);
    }

    #[Route('/boutique/{id}/acheter', name: 'app_boutique_acheter', methods: ['POST'])]
    public function acheter(ArticleBoutique $article, Request $request, Boutique $boutique): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }
        if (!$this->isCsrfTokenValid('acheter-article-'.$article->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        try { $boutique->acheter($utilisateur, $article); $this->addFlash('success', 'Achat réussi : '.$article->getNom().' rejoint ta collection.'); }
        catch (\DomainException $exception) { $this->addFlash('danger', $exception->getMessage()); }
        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/boutique/{id}/equiper', name: 'app_boutique_equiper', methods: ['POST'])]
    public function equiper(ArticleBoutique $article, Request $request, AchatBoutiqueRepository $achats, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur || !$this->isCsrfTokenValid('equiper-article-'.$article->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        if ($article->getType() !== TypeArticleBoutique::Titre || !$achats->findOneBy(['utilisateur' => $utilisateur, 'article' => $article])) { throw $this->createAccessDeniedException('Tu ne possèdes pas ce titre.'); }
        $utilisateur->setTitreEquipe($article); $entityManager->flush();
        $this->addFlash('success', 'Le titre « '.$article->getNom().' » est maintenant équipé.');
        return $this->redirectToRoute('app_boutique');
    }
}
