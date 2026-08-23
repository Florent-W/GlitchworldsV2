<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/administration/export')]
final class AdministrationExportController extends AbstractController
{
    #[Route('/{type}', name: 'app_administration_export', requirements: ['type' => 'vues|jeux|actualites|membres|moderation'], methods: ['GET'])]
    public function exporter(string $type, Connection $db): StreamedResponse
    {
        [$entetes, $sql] = match ($type) {
            'vues' => [['Date', 'Chemin', 'Type', 'Contenu ID', 'Visiteur'], 'SELECT vue_le, chemin, type_contenu, contenu_id, visiteur_hash FROM vue_page ORDER BY vue_le DESC'],
            'jeux' => [['ID', 'Nom', 'Slug', 'Statut', 'Créé le'], 'SELECT id, nom, slug, statut, cree_le FROM jeu ORDER BY id'],
            'actualites' => [['ID', 'Titre', 'Slug', 'Statut', 'Publiée le'], 'SELECT id, titre, slug, statut, publiee_le FROM actualite ORDER BY id'],
            'membres' => [['ID', 'Pseudo', 'E-mail', 'Rôles', 'Inscrit le', 'Points', 'Expérience'], 'SELECT id, pseudo, email, roles, inscrit_le, points, experience FROM utilisateur ORDER BY id'],
            default => [['Date', 'Modérateur', 'Action', 'Type', 'Cible ID', 'Résumé'], 'SELECT a.effectuee_le, COALESCE(u.pseudo, "Compte supprimé"), a.action, a.type_cible, a.cible_id, a.resume FROM action_moderation a LEFT JOIN utilisateur u ON u.id = a.moderateur_id ORDER BY a.effectuee_le DESC'],
        };
        $reponse = new StreamedResponse(static function () use ($db, $sql, $entetes): void {
            $sortie = fopen('php://output', 'wb');
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, $entetes, ';');

            foreach ($db->iterateAssociative($sql) as $ligne) {
                $valeurs = array_map(
                    static fn (mixed $valeur): mixed => is_string($valeur) && preg_match('/^[=+\-@]/', $valeur)
                        ? "'".$valeur
                        : $valeur,
                    array_values($ligne),
                );
                fputcsv($sortie, $valeurs, ';');
            }

            fclose($sortie);
        });
        $reponse->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $reponse->headers->set('Content-Disposition', 'attachment; filename="glitchworlds-'.$type.'-'.date('Y-m-d').'.csv"');
        return $reponse;
    }
}
