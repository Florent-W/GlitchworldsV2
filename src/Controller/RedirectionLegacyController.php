<?php

namespace App\Controller;

use App\Entity\Actualite;
use App\Entity\Jeu;
use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use App\Enum\StatutJeu;
use App\Repository\ActualiteRepository;
use App\Repository\JeuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Redirections permanentes des URL publiques de l'ancien site. */
final class RedirectionLegacyController extends AbstractController
{
    #[Route('/news/{slug}-{id}', name: 'app_legacy_actualite', requirements: ['slug' => '[a-zA-Z0-9-]+', 'id' => '\\d+'], methods: ['GET', 'HEAD'], priority: 100)]
    public function actualite(int $id, ActualiteRepository $actualites): Response
    {
        $actualite = $actualites->find($id);
        if (!$actualite instanceof Actualite || $actualite->getStatut() !== StatutActualite::Publiee) {
            throw $this->createNotFoundException('Cette ancienne actualité n’existe plus.');
        }

        if ($actualite->getCategorie() === CategorieActualite::Mods && $actualite->getFicheJeu() instanceof Jeu) {
            $jeu = $actualite->getFicheJeu();

            return $this->redirectToRoute('app_jeu_show', ['slug' => $jeu->getSlug(), 'id' => $jeu->getId()], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->redirectToRoute('app_actualite_voir', [
            'slug' => $actualite->getSlug(),
            'id' => $actualite->getId(),
        ], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/jeu/{slug}-{id}/{onglet}', name: 'app_legacy_jeu_onglet', requirements: ['slug' => '[a-zA-Z0-9-]+', 'id' => '\\d+', 'onglet' => 'avis|news|glitchs|mods|tutoriels'], methods: ['GET', 'HEAD'], priority: 100)]
    public function jeuAvecOnglet(int $id, JeuRepository $jeux): Response
    {
        return $this->redirigerJeu($id, $jeux);
    }

    #[Route('/news.php', name: 'app_legacy_actualite_php', methods: ['GET', 'HEAD'], priority: 100)]
    public function actualitePhp(Request $request, ActualiteRepository $actualites): Response
    {
        $id = $request->query->getInt('id');

        return $id > 0
            ? $this->actualite($id, $actualites)
            : $this->redirectToRoute('app_actualites', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/jeu.php', name: 'app_legacy_jeu_php', methods: ['GET', 'HEAD'], priority: 100)]
    public function jeuPhp(Request $request, JeuRepository $jeux): Response
    {
        $id = $request->query->getInt('id');

        return $id > 0
            ? $this->redirigerJeu($id, $jeux)
            : $this->redirectToRoute('app_jeux', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/recherche.php', name: 'app_legacy_recherche_php', methods: ['GET', 'HEAD'], priority: 100)]
    public function recherchePhp(Request $request): Response
    {
        $recherche = trim($request->query->getString('recherche'));
        $type = mb_strtolower($request->query->getString('categorie'));
        if ($type !== 'jeux' && $recherche !== '') {
            return $this->redirectToRoute('app_recherche', ['recherche' => $recherche], Response::HTTP_MOVED_PERMANENTLY);
        }

        $categories = [
            'officiel' => 'officiels',
            'officiels' => 'officiels',
            'rom hack' => 'rom-hacks',
            'rom hacks' => 'rom-hacks',
            'fan game' => 'fan-games',
            'fan games' => 'fan-games',
        ];
        $categorie = mb_strtolower(trim($request->query->getString('categorie_jeu')));
        $parametres = [];
        if ($recherche !== '') {
            $parametres['recherche'] = $recherche;
        }
        if (isset($categories[$categorie])) {
            $parametres['categorie'] = $categories[$categorie];
        }
        if (($page = $request->query->getInt('page', 1)) > 1) {
            $parametres['page'] = $page;
        }

        return $this->redirectToRoute('app_jeux', $parametres, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/articles/{categorie}', name: 'app_legacy_articles', requirements: ['categorie' => 'news|glitchs|tutoriels|mods'], methods: ['GET', 'HEAD'], priority: 100)]
    public function articles(string $categorie): Response
    {
        if ($categorie === CategorieActualite::Mods->value) {
            return $this->redirectToRoute('app_jeux', ['categorie' => 'mods'], Response::HTTP_MOVED_PERMANENTLY);
        }

        $route = $categorie === CategorieActualite::Glitchs->value ? 'app_actualites_glitchs' : 'app_actualites';
        $parametres = $route === 'app_actualites' ? ['categorie' => $categorie] : [];

        return $this->redirectToRoute($route, $parametres, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/liste/Jeux/{categorie?}', name: 'app_legacy_liste_jeux', requirements: ['categorie' => '[A-Za-z+ -]+'], methods: ['GET', 'HEAD'], priority: 100)]
    public function listeJeux(?string $categorie = null): Response
    {
        $categories = [
            'officiel' => 'officiels',
            'officiels' => 'officiels',
            'rom+hack' => 'rom-hacks',
            'rom+hacks' => 'rom-hacks',
            'fan+game' => 'fan-games',
            'fan+games' => 'fan-games',
        ];
        $cle = mb_strtolower((string) $categorie);
        $parametres = isset($categories[$cle]) ? ['categorie' => $categories[$cle]] : [];

        return $this->redirectToRoute('app_jeux', $parametres, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/index.php', name: 'app_legacy_index_php', methods: ['GET', 'HEAD'], priority: 100)]
    public function indexPhp(): Response
    {
        return $this->redirectToRoute('app_home', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/connexion.php', name: 'app_legacy_connexion_php', methods: ['GET', 'HEAD'], priority: 100)]
    public function connexionPhp(): Response
    {
        return $this->redirectToRoute('app_connexion', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/inscription.php', name: 'app_legacy_inscription_php', methods: ['GET', 'HEAD'], priority: 100)]
    public function inscriptionPhp(): Response
    {
        return $this->redirectToRoute('app_inscription', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/creation_news.php', name: 'app_legacy_creation_actualite', methods: ['GET', 'HEAD'], priority: 100)]
    public function creationActualite(): Response
    {
        return $this->redirectToRoute('app_actualite_proposer', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/creation_jeu.php', name: 'app_legacy_creation_jeu', methods: ['GET', 'HEAD'], priority: 100)]
    public function creationJeu(): Response
    {
        return $this->redirectToRoute('app_jeu_proposer', status: Response::HTTP_MOVED_PERMANENTLY);
    }

    private function redirigerJeu(int $id, JeuRepository $jeux): Response
    {
        $jeu = $jeux->find($id);
        if (!$jeu instanceof Jeu || $jeu->getStatut() !== StatutJeu::Approuve) {
            throw $this->createNotFoundException('Cet ancien jeu n’existe plus.');
        }

        return $this->redirectToRoute('app_jeu_show', [
            'slug' => $jeu->getSlug(),
            'id' => $jeu->getId(),
        ], Response::HTTP_MOVED_PERMANENTLY);
    }
}
