<?php

namespace App\Controller;

use App\Entity\CommentaireActualite;
use App\Entity\CommentaireJeu;
use App\Repository\AvisRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ContenuAccueil;
use App\Service\StatistiquesPlateforme;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function accueil(
        UtilisateurRepository $utilisateurRepository,
        CommentaireJeuRepository $commentaireJeuRepository,
        CommentaireActualiteRepository $commentaireActualiteRepository,
        AvisRepository $avisRepository,
        ContenuAccueil $contenuAccueil,
        StatistiquesPlateforme $statistiquesPlateforme,
    ): Response
    {
        $nouveautes = $contenuAccueil->nouveautes();
        $populaires = $contenuAccueil->populaires();
        $dernieresActualites = $contenuAccueil->dernieresActualites();
        $derniersGlitchs = $contenuAccueil->derniersGlitchs();
        $statistiques = $statistiquesPlateforme->obtenir();

        return $this->render('home/index.html.twig', [
            'nouveautes' => $nouveautes,
            'populaires' => $populaires,
            'notesJeux' => $avisRepository->trouverResumesPour([...$populaires, ...$nouveautes]),
            'commentairesJeux' => $commentaireJeuRepository->compterParJeux([...$populaires, ...$nouveautes]),
            'dernieresActualites' => $dernieresActualites,
            'derniersGlitchs' => $derniersGlitchs,
            'commentairesActualites' => $commentaireActualiteRepository->compterParActualites([...$dernieresActualites, ...$derniersGlitchs]),
            'actualitesMisesEnAvant' => $contenuAccueil->misesEnAvant(),
            'activiteRecente' => $this->construireActiviteRecente(
                $commentaireJeuRepository->trouverDerniersPublics(6),
                $commentaireActualiteRepository->trouverDerniersPublics(6),
            ),
            'nouveauxMembres' => $utilisateurRepository->trouverRecents(6),
            'totalJeux' => $statistiques['jeux'],
            'totalMembres' => $statistiques['membres'],
            'totalCommentaires' => $statistiques['commentaires'],
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
