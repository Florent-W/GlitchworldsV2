<?php

namespace App\Controller;

use App\Entity\AchatBoutique;
use App\Entity\ArticleBoutique;
use App\Entity\Utilisateur;
use App\Entity\Jeu;
use App\Enum\StatutJeu;
use App\Enum\TypeArticleBoutique;
use App\Repository\AchatBoutiqueRepository;
use App\Repository\ArticleBoutiqueRepository;
use App\Repository\JeuRepository;
use App\Service\Boutique;
use App\Service\GestionSucces;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BoutiqueController extends AbstractController
{
    use AnnonceSuccesTrait;

    #[Route('/boutique', name: 'app_boutique', methods: ['GET'])]
    public function index(ArticleBoutiqueRepository $articles, AchatBoutiqueRepository $achats, JeuRepository $jeux): Response
    {
        $utilisateur = $this->getUser();
        $possessions = $utilisateur instanceof Utilisateur ? $achats->trouverPourUtilisateur($utilisateur) : [];
        $quantitesArticles = [];
        foreach ($possessions as $achat) { if ($achat->getArticle()) { $quantitesArticles[$achat->getArticle()->getId()] = $achat->getQuantite(); } }
        return $this->render('boutique/index.html.twig', ['articles' => $articles->trouverActifs(), 'achats' => $possessions, 'articlesPossedes' => array_map(static fn (AchatBoutique $achat) => $achat->getArticle()?->getId(), $possessions), 'quantitesArticles' => $quantitesArticles, 'fichesCreees' => $utilisateur instanceof Utilisateur ? $jeux->findBy(['createur' => $utilisateur, 'statut' => StatutJeu::Approuve], ['nom' => 'ASC']) : []]);
    }

    #[Route('/boutique/{id}/acheter', name: 'app_boutique_acheter', methods: ['POST'])]
    public function acheter(ArticleBoutique $article, Request $request, Boutique $boutique, GestionSucces $gestionSucces): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur) { throw $this->createAccessDeniedException(); }
        if (!$this->isCsrfTokenValid('acheter-article-'.$article->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        try {
            $boutique->acheter($utilisateur, $article);
            $this->verifierEtAnnoncerSucces($utilisateur, $gestionSucces);
            $this->addFlash('success', 'Récompense débloquée : '.$article->getNom().' rejoint ta collection.');
        }
        catch (\DomainException $exception) { $this->addFlash('danger', $exception->getMessage()); }
        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/boutique/{id}/equiper', name: 'app_boutique_equiper', methods: ['POST'])]
    public function equiper(ArticleBoutique $article, Request $request, AchatBoutiqueRepository $achats, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur || !$this->isCsrfTokenValid('equiper-article-'.$article->getId(), $request->request->getString('_token'))) { throw $this->createAccessDeniedException(); }
        if (in_array($article->getType(), [TypeArticleBoutique::Badge, TypeArticleBoutique::Vitrine], true) || !$achats->findOneBy(['utilisateur' => $utilisateur, 'article' => $article])) { throw $this->createAccessDeniedException('Tu ne possèdes pas cet élément équipable.'); }
        match ($article->getType()) {
            TypeArticleBoutique::Titre => $utilisateur->setTitreEquipe($article),
            TypeArticleBoutique::Effet => $utilisateur->setEffetProfilEquipe($article),
            TypeArticleBoutique::Cadre => $utilisateur->setCadreAvatarEquipe($article),
            TypeArticleBoutique::Badge => null,
            TypeArticleBoutique::Vitrine => null,
        };
        $entityManager->flush();
        $this->addFlash('success', '« '.$article->getNom().' » est maintenant équipé.');
        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/boutique/{id}/desequiper', name: 'app_boutique_desequiper', methods: ['POST'])]
    public function desequiper(ArticleBoutique $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur || !$this->isCsrfTokenValid('desequiper-article-'.$article->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $estTitreEquipe = $article->getType() === TypeArticleBoutique::Titre && $utilisateur->getTitreEquipe() === $article;
        $estEffetEquipe = $article->getType() === TypeArticleBoutique::Effet && $utilisateur->getEffetProfilEquipe() === $article;
        $estCadreEquipe = $article->getType() === TypeArticleBoutique::Cadre && $utilisateur->getCadreAvatarEquipe() === $article;
        if (!$estTitreEquipe && !$estEffetEquipe && !$estCadreEquipe) {
            throw $this->createAccessDeniedException('Cet élément n’est pas équipé.');
        }

        if ($estTitreEquipe) {
            $utilisateur->setTitreEquipe(null);
        } elseif ($estEffetEquipe) {
            $utilisateur->setEffetProfilEquipe(null);
        } else {
            $utilisateur->setCadreAvatarEquipe(null);
        }
        $entityManager->flush();
        $this->addFlash('success', '« '.$article->getNom().' » a été déséquipé.');

        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/boutique/vitrine/selectionner', name: 'app_boutique_vitrine_selectionner', methods: ['POST'])]
    public function selectionnerVitrine(Request $request, ArticleBoutiqueRepository $articles, AchatBoutiqueRepository $achats, JeuRepository $jeux, EntityManagerInterface $entityManager): Response
    {
        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof Utilisateur || !$this->isCsrfTokenValid('selectionner-vitrine', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $article = $articles->findOneBy(['type' => TypeArticleBoutique::Vitrine, 'actif' => true]);
        if (!$article || !$achats->findOneBy(['utilisateur' => $utilisateur, 'article' => $article])) {
            throw $this->createAccessDeniedException('Tu ne possèdes pas la Vitrine de créateur.');
        }

        $achatVitrine = $achats->findOneBy(['utilisateur' => $utilisateur, 'article' => $article]);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->request->all('jeux')))));
        if (count($ids) > min($achatVitrine->getQuantite(), Boutique::MAX_VITRINES)) {
            throw $this->createAccessDeniedException('Tu ne disposes pas d’assez d’emplacements de vitrine.');
        }
        $selection = [];
        foreach ($ids as $jeuId) {
            $jeu = $jeux->find($jeuId);
            if (!$jeu instanceof Jeu || $jeu->getCreateur() !== $utilisateur || $jeu->getStatut() !== StatutJeu::Approuve) { throw $this->createAccessDeniedException('Cette fiche ne peut pas être mise en avant.'); }
            $selection[] = $jeu;
        }
        $utilisateur->viderFichesMisesEnAvant();
        foreach ($selection as $jeu) { $utilisateur->ajouterFicheMiseEnAvant($jeu); }
        $utilisateur->setFicheMiseEnAvant($selection[0] ?? null);
        $entityManager->flush();
        $this->addFlash('success', $selection ? count($selection).' fiche'.(count($selection) > 1 ? 's' : '').' présentée'.(count($selection) > 1 ? 's' : '').' dans ta vitrine.' : 'La vitrine a été retirée de ton profil.');

        return $this->redirectToRoute('app_boutique');
    }
}
