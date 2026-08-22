<?php

namespace App\Controller;

use App\Entity\CommentaireActualite;
use App\Entity\CommentaireJeu;
use App\Enum\CategorieActualite;
use App\Enum\StatutJeu;
use App\Repository\AvisRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\ActualiteRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\JeuRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function accueil(
        JeuRepository $jeuRepository,
        UtilisateurRepository $utilisateurRepository,
        CommentaireJeuRepository $commentaireJeuRepository,
        ActualiteRepository $actualiteRepository,
        CommentaireActualiteRepository $commentaireActualiteRepository,
        AvisRepository $avisRepository,
    ): Response
    {
        $nouveautes = $jeuRepository->trouverNouveautes(9);
        $populaires = $jeuRepository->trouverPopulaires(9);
        $dernieresActualites = $actualiteRepository->trouverDernieres(9);
        $derniersGlitchs = $actualiteRepository->trouverDernieres(9, CategorieActualite::Glitchs);

        return $this->render('home/index.html.twig', [
            'nouveautes' => $nouveautes,
            'populaires' => $populaires,
            'notesJeux' => $avisRepository->trouverResumesPour([...$populaires, ...$nouveautes]),
            'commentairesJeux' => $commentaireJeuRepository->compterParJeux([...$populaires, ...$nouveautes]),
            'dernieresActualites' => $dernieresActualites,
            'derniersGlitchs' => $derniersGlitchs,
            'commentairesActualites' => $commentaireActualiteRepository->compterParActualites([...$dernieresActualites, ...$derniersGlitchs]),
            'actualitesMisesEnAvant' => $actualiteRepository->trouverMisesEnAvant(),
            'activiteRecente' => $this->construireActiviteRecente(
                $commentaireJeuRepository->trouverDerniersPublics(6),
                $commentaireActualiteRepository->trouverDerniersPublics(6),
            ),
            'nouveauxMembres' => $utilisateurRepository->trouverRecents(6),
            'totalJeux' => $jeuRepository->count(['statut' => StatutJeu::Approuve]),
            'totalMembres' => $utilisateurRepository->count([]),
            'totalCommentaires' => $commentaireJeuRepository->count([]) + $commentaireActualiteRepository->count([]),
        ]);
    }

    /**
     * Fusionne commentaires jeux + actualités en une timeline unique, triée du plus récent au plus ancien.
     *
     * @param list<CommentaireJeu> $commentairesJeux
     * @param list<CommentaireActualite> $commentairesActualites
     * @return list<array{type: string, auteur: ?\App\Entity\Utilisateur, extrait: string, cible: string, url: string, date: \DateTimeImmutable}>
     */
    private function construireActiviteRecente(array $commentairesJeux, array $commentairesActualites): array
    {
        $activite = [];

        foreach ($commentairesJeux as $commentaire) {
            $jeu = $commentaire->getJeu();
            if ($jeu === null) {
                continue;
            }

            $activite[] = [
                'type' => 'jeu',
                'auteur' => $commentaire->getAuteur(),
                'extrait' => $this->tronquer($commentaire->getContenu()),
                'cible' => $jeu->getNom(),
                'url' => $this->generateUrl('app_jeu_show', ['slug' => $jeu->getSlug(), 'id' => $jeu->getId()]).'#commentaire-'.$commentaire->getId(),
                'date' => $commentaire->getDateCommentaire(),
            ];
        }

        foreach ($commentairesActualites as $commentaire) {
            $actualite = $commentaire->getActualite();
            if ($actualite === null) {
                continue;
            }

            $activite[] = [
                'type' => 'actualite',
                'auteur' => $commentaire->getAuteur(),
                'extrait' => $this->tronquer($commentaire->getContenu()),
                'cible' => $actualite->getTitre(),
                'url' => $this->generateUrl('app_actualite_voir', ['slug' => $actualite->getSlug(), 'id' => $actualite->getId()]).'#commentaire-'.$commentaire->getId(),
                'date' => $commentaire->getDateCommentaire(),
            ];
        }

        usort($activite, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return array_slice($activite, 0, 8);
    }

    private function tronquer(string $texte, int $longueur = 110): string
    {
        $texte = trim(preg_replace('/\s+/u', ' ', $texte) ?? '');
        if (mb_strlen($texte) <= $longueur) {
            return $texte;
        }

        return rtrim(mb_substr($texte, 0, $longueur - 1)).'…';
    }
}
