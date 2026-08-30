<?php

namespace App\Controller;

use App\Enum\StatutJeu;
use App\Repository\ActualiteRepository;
use App\Repository\CommentaireActualiteRepository;
use App\Repository\CommentaireJeuRepository;
use App\Repository\JeuRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\SignalementRepository;
use App\Enum\StatutSignalement;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\StatistiquesAdministration;
use App\Entity\ActionModeration;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/administration', name: 'app_administration_')]
final class AdministrationController extends AbstractController
{
    #[Route('', name: 'tableau_de_bord', methods: ['GET'])]
    public function tableauDeBord(
        Request $request,
        JeuRepository $jeux,
        UtilisateurRepository $utilisateurs,
        CommentaireJeuRepository $commentairesJeux,
        CommentaireActualiteRepository $commentairesActualites,
        ActualiteRepository $actualites,
        SignalementRepository $signalements,
        Connection $connexion,
        StatistiquesAdministration $statistiquesAdministration,
        EntityManagerInterface $entityManager,
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
                'texte' => 'a rejoint Glitchworlds',
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
                'signalements' => $signalements->count(['statut' => StatutSignalement::EnAttente]),
            ],
            'jeuxEnAttente' => $jeux->count(['statut' => StatutJeu::EnAttente]),
            'derniersJeux' => $jeux->findBy([], ['creeLe' => 'DESC'], 5),
            'activites' => array_slice($activites, 0, 8),
            'tendances' => $this->construireTendances($connexion),
            'audience' => $statistiquesAdministration->construire($request->query->getInt('periode', 30)),
            'journalModeration' => $entityManager->getRepository(ActionModeration::class)->findBy([], ['effectueeLe' => 'DESC'], 10),
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

    /** @return array{graphique: list<array{jour: string, membres: int, commentaires: int}>, maximum: int, membres30: int, commentaires30: int, jeux30: int, evolutionMembres: ?int, evolutionCommentaires: ?int} */
    private function construireTendances(Connection $connexion): array
    {
        $aujourdhui = new \DateTimeImmutable('today');
        $debutGraphique = $aujourdhui->modify('-6 days');
        $debutPeriode = $aujourdhui->modify('-29 days');
        $debutPrecedente = $debutPeriode->modify('-30 days');

        $membresParJour = $connexion->fetchAllKeyValue(
            'SELECT DATE(inscrit_le) AS jour, COUNT(*) AS total FROM utilisateur WHERE inscrit_le >= :debut GROUP BY DATE(inscrit_le)',
            ['debut' => $debutGraphique->format('Y-m-d 00:00:00')],
        );
        $commentairesParJour = $connexion->fetchAllKeyValue(
            'SELECT jour, SUM(total) AS total FROM (
                SELECT DATE(date_commentaire) AS jour, COUNT(*) AS total FROM commentaire_jeu WHERE date_commentaire >= :debutJeu GROUP BY DATE(date_commentaire)
                UNION ALL
                SELECT DATE(date_commentaire) AS jour, COUNT(*) AS total FROM commentaire_actualite WHERE date_commentaire >= :debutActualite GROUP BY DATE(date_commentaire)
            ) activite GROUP BY jour',
            ['debutJeu' => $debutGraphique->format('Y-m-d 00:00:00'), 'debutActualite' => $debutGraphique->format('Y-m-d 00:00:00')],
        );

        $graphique = [];
        $maximum = 1;
        $jours = ['Sun' => 'Dim', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam'];
        for ($index = 0; $index < 7; ++$index) {
            $date = $debutGraphique->modify('+'.$index.' days');
            $membres = (int) ($membresParJour[$date->format('Y-m-d')] ?? 0);
            $commentaires = (int) ($commentairesParJour[$date->format('Y-m-d')] ?? 0);
            $maximum = max($maximum, $membres, $commentaires);
            $graphique[] = ['jour' => $jours[$date->format('D')], 'membres' => $membres, 'commentaires' => $commentaires];
        }

        $periodesMembres = $this->compterPeriodes($connexion, 'utilisateur', 'inscrit_le', $debutPrecedente, $debutPeriode, $aujourdhui->modify('+1 day'));
        $periodesJeux = $this->compterPeriodes($connexion, 'jeu', 'cree_le', $debutPrecedente, $debutPeriode, $aujourdhui->modify('+1 day'));
        $periodesCommentaires = $connexion->fetchAssociative(
            'SELECT
                SUM(CASE WHEN date_commentaire >= :courante THEN 1 ELSE 0 END) AS courante,
                SUM(CASE WHEN date_commentaire >= :precedente AND date_commentaire < :couranteBis THEN 1 ELSE 0 END) AS precedente
             FROM (
                SELECT date_commentaire FROM commentaire_jeu WHERE date_commentaire >= :borneJeu
                UNION ALL SELECT date_commentaire FROM commentaire_actualite WHERE date_commentaire >= :borneActualite
             ) commentaires',
            [
                'courante' => $debutPeriode->format('Y-m-d 00:00:00'),
                'precedente' => $debutPrecedente->format('Y-m-d 00:00:00'),
                'couranteBis' => $debutPeriode->format('Y-m-d 00:00:00'),
                'borneJeu' => $debutPrecedente->format('Y-m-d 00:00:00'),
                'borneActualite' => $debutPrecedente->format('Y-m-d 00:00:00'),
            ],
        );

        $commentairesCourants = (int) ($periodesCommentaires['courante'] ?? 0);
        $commentairesPrecedents = (int) ($periodesCommentaires['precedente'] ?? 0);

        return [
            'graphique' => $graphique,
            'maximum' => $maximum,
            'membres30' => $periodesMembres['courante'],
            'commentaires30' => $commentairesCourants,
            'jeux30' => $periodesJeux['courante'],
            'evolutionMembres' => $this->calculerEvolution($periodesMembres['courante'], $periodesMembres['precedente']),
            'evolutionCommentaires' => $this->calculerEvolution($commentairesCourants, $commentairesPrecedents),
        ];
    }

    /** @return array{courante: int, precedente: int} */
    private function compterPeriodes(Connection $connexion, string $table, string $colonne, \DateTimeImmutable $precedente, \DateTimeImmutable $courante, \DateTimeImmutable $fin): array
    {
        $resultat = $connexion->fetchAssociative(sprintf(
            'SELECT SUM(CASE WHEN %1$s >= :courante AND %1$s < :fin THEN 1 ELSE 0 END) AS courante, SUM(CASE WHEN %1$s >= :precedente AND %1$s < :couranteBis THEN 1 ELSE 0 END) AS precedente FROM %2$s WHERE %1$s >= :borne',
            $colonne,
            $table,
        ), [
            'courante' => $courante->format('Y-m-d 00:00:00'),
            'fin' => $fin->format('Y-m-d 00:00:00'),
            'precedente' => $precedente->format('Y-m-d 00:00:00'),
            'couranteBis' => $courante->format('Y-m-d 00:00:00'),
            'borne' => $precedente->format('Y-m-d 00:00:00'),
        ]);

        return ['courante' => (int) ($resultat['courante'] ?? 0), 'precedente' => (int) ($resultat['precedente'] ?? 0)];
    }

    private function calculerEvolution(int $courante, int $precedente): ?int
    {
        if ($precedente === 0) {
            return $courante === 0 ? 0 : null;
        }

        return (int) round((($courante - $precedente) / $precedente) * 100);
    }
}
