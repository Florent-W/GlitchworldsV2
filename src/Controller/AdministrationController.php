<?php

namespace App\Controller;

use App\Enum\StatutJeu;
use App\Repository\ActualiteRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/administration', name: 'app_administration_')]
final class AdministrationController extends AbstractController
{
    #[Route('', name: 'tableau_de_bord', methods: ['GET'])]
    public function tableauDeBord(
        JeuRepository $jeux,
        UtilisateurRepository $utilisateurs,
        CommentaireJeuRepository $commentairesJeux,
        CommentaireActualiteRepository $commentairesActualites,
        ActualiteRepository $actualites,
    ): Response {
        $activites = [];
        foreach ($commentairesJeux->findBy([], ['dateCommentaire' => 'DESC'], 4) as $commentaire) {
            $activites[] = [
                'date' => $commentaire->getDateCommentaire(),
                'icone' => 'chat-dots-fill',
                'couleur' => 'primary',
                'auteur' => $commentaire->getAuteur()?->getPseudo() ?? 'Compte supprimé',
                'texte' => 'a commenté le jeu '.$commentaire->getJeu()?->getNom(),
                'url' => $commentaire->getJeu() ? $this->generateUrl('app_jeu_show', ['slug' => $commentaire->getJeu()->getSlug(), 'id' => $commentaire->getJeu()->getId()]) : null,
            ];
        }
        foreach ($commentairesActualites->findBy([], ['dateCommentaire' => 'DESC'], 4) as $commentaire) {
            $activites[] = [
                'date' => $commentaire->getDateCommentaire(),
                'icone' => 'newspaper',
                'couleur' => 'info',
                'auteur' => $commentaire->getAuteur()?->getPseudo() ?? 'Compte supprimé',
                'texte' => 'a commenté l’actualité '.$commentaire->getActualite()?->getTitre(),
                'url' => $commentaire->getActualite() ? $this->generateUrl('app_actualite_voir', ['slug' => $commentaire->getActualite()->getSlug(), 'id' => $commentaire->getActualite()->getId()]) : null,
            ];
        }
        foreach ($utilisateurs->findBy([], ['inscritLe' => 'DESC'], 4) as $utilisateur) {
            $activites[] = [
                'date' => $utilisateur->getInscritLe(),
                'icone' => 'person-plus-fill',
                'couleur' => 'success',
                'auteur' => $utilisateur->getPseudo(),
                'texte' => 'a rejoint GlitchWorlds',
                'url' => $this->generateUrl('app_profil', ['id' => $utilisateur->getId()]),
            ];
        }
        usort($activites, static fn (array $a, array $b): int => $b['date'] <=> $a['date']);

        return $this->render('administration/tableau_de_bord.html.twig', [
            'statistiques' => [
                'jeux' => $jeux->count([]),
                'membres' => $utilisateurs->count([]),
                'commentaires' => $commentairesJeux->count([]) + $commentairesActualites->count([]),
                'actualites' => $actualites->count([]),
            ],
            'jeuxEnAttente' => $jeux->count(['statut' => StatutJeu::EnAttente]),
            'derniersJeux' => $jeux->findBy([], ['creeLe' => 'DESC'], 5),
            'activites' => array_slice($activites, 0, 8),
        ]);
    }

    #[Route('/membres', name: 'membres', methods: ['GET'])]
    public function membres(Request $request, UtilisateurRepository $utilisateurs): Response
    {
        $recherche = trim((string) $request->query->get('recherche'));

        return $this->render('administration/membres.html.twig', [
            'membres' => $utilisateurs->rechercherPourAdministration($recherche),
            'recherche' => $recherche,
        ]);
    }
}
