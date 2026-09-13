<?php
namespace App\Service;
use Doctrine\DBAL\Connection;
final class StatistiquesAdministration
{
    public function __construct(private Connection $db) {}
    public function construire(int $jours): array
    {
        $jours = in_array($jours, [7, 28, 30, 90, 180, 365], true) ? $jours : 30;
        $debut = (new \DateTimeImmutable('today'))->modify('-'.($jours - 1).' days');
        $debutPrecedent = $debut->modify('-'.$jours.' days');
        $vues = $this->db->fetchAllAssociative('SELECT DATE(vue_le) jour, COUNT(*) vues, COUNT(DISTINCT visiteur_hash) visiteurs FROM vue_page WHERE vue_le >= :debut GROUP BY DATE(vue_le)', ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $index = []; foreach ($vues as $ligne) { $index[$ligne['jour']] = $ligne; }
        $graphique = []; $maximum = 1;
        for ($i = 0; $i < $jours; ++$i) { $date = $debut->modify('+'.$i.' days'); $ligne = $index[$date->format('Y-m-d')] ?? ['vues' => 0, 'visiteurs' => 0]; $maximum = max($maximum, (int) $ligne['vues'], (int) $ligne['visiteurs']); $graphique[] = ['date' => $date, 'jour' => $date->format($jours <= 7 ? 'D' : 'd/m'), 'vues' => (int) $ligne['vues'], 'visiteurs' => (int) $ligne['visiteurs']]; }
        if ($jours > 90) {
            $graphique = $this->graphiqueMensuel($debut);
            $maximum = 1;
            foreach ($graphique as $point) {
                $maximum = max($maximum, $point['vues'], $point['visiteurs']);
            }
        }
        $totaux = $this->db->fetchAssociative('SELECT COUNT(*) vues, COUNT(DISTINCT visiteur_hash) visiteurs FROM vue_page WHERE vue_le >= :debut', ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $totauxPrecedents = $this->db->fetchAssociative('SELECT COUNT(*) vues, COUNT(DISTINCT visiteur_hash) visiteurs FROM vue_page WHERE vue_le >= :debut AND vue_le < :fin', ['debut' => $debutPrecedent->format('Y-m-d 00:00:00'), 'fin' => $debut->format('Y-m-d 00:00:00')]);
        $contenusVus = (int) $this->db->fetchOne("SELECT COUNT(DISTINCT CONCAT(type_contenu, '-', contenu_id)) FROM vue_page WHERE vue_le >= :debut AND contenu_id IS NOT NULL AND type_contenu IN ('jeu', 'actualite')", ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $contenusSansVue = (int) $this->db->fetchOne("SELECT COUNT(*) FROM (
            SELECT j.id FROM jeu j LEFT JOIN vue_page v ON v.type_contenu = 'jeu' AND v.contenu_id = j.id AND v.vue_le >= :debutJeux WHERE j.statut = 'approuve' GROUP BY j.id HAVING COUNT(v.id) = 0
            UNION ALL
            SELECT a.id FROM actualite a LEFT JOIN vue_page v ON v.type_contenu = 'actualite' AND v.contenu_id = a.id AND v.vue_le >= :debutActualites WHERE a.statut = 'publiee' GROUP BY a.id HAVING COUNT(v.id) = 0
        ) contenus", ['debutJeux' => $debut->format('Y-m-d 00:00:00'), 'debutActualites' => $debut->format('Y-m-d 00:00:00')]);
        $totalVues = (int) ($totaux['vues'] ?? 0);
        $totalVisiteurs = (int) ($totaux['visiteurs'] ?? 0);
        $vuesPrecedentes = (int) ($totauxPrecedents['vues'] ?? 0);
        $evolutionVues = $vuesPrecedentes > 0 ? (int) round((($totalVues - $vuesPrecedentes) / $vuesPrecedentes) * 100) : ($totalVues > 0 ? 100 : 0);
        $jeux = $this->db->fetchAllAssociative("SELECT j.id, j.nom titre, j.slug, COUNT(v.id) vues, COUNT(DISTINCT v.visiteur_hash) visiteurs FROM jeu j LEFT JOIN vue_page v ON v.type_contenu = 'jeu' AND v.contenu_id = j.id AND v.vue_le >= :debut GROUP BY j.id, j.nom, j.slug ORDER BY vues DESC LIMIT 10", ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $actualites = $this->db->fetchAllAssociative("SELECT a.id, a.titre, a.slug, COUNT(v.id) vues, COUNT(DISTINCT v.visiteur_hash) visiteurs FROM actualite a LEFT JOIN vue_page v ON v.type_contenu = 'actualite' AND v.contenu_id = a.id AND v.vue_le >= :debut GROUP BY a.id, a.titre, a.slug ORDER BY vues DESC LIMIT 10", ['debut' => $debut->format('Y-m-d 00:00:00')]);
        return ['jours' => $jours, 'graphique' => $graphique, 'maximum' => $maximum, 'vues' => $totalVues, 'visiteurs' => $totalVisiteurs, 'evolutionVues' => $evolutionVues, 'pagesParVisiteur' => $totalVisiteurs > 0 ? $totalVues / $totalVisiteurs : 0, 'contenusVus' => $contenusVus, 'contenusSansVue' => $contenusSansVue, 'jeux' => $jeux, 'actualites' => $actualites];
    }

    private function graphiqueMensuel(\DateTimeImmutable $debut): array
    {
        $lignes = $this->db->fetchAllAssociative("SELECT DATE_FORMAT(vue_le, '%Y-%m') AS mois, COUNT(*) AS vues, COUNT(DISTINCT visiteur_hash) AS visiteurs FROM vue_page WHERE vue_le >= :debut GROUP BY DATE_FORMAT(vue_le, '%Y-%m') ORDER BY mois", ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $index = [];
        foreach ($lignes as $ligne) {
            $index[$ligne['mois']] = $ligne;
        }
        $points = [];
        $fin = new \DateTimeImmutable('first day of this month');
        for ($date = $debut->modify('first day of this month'); $date <= $fin; $date = $date->modify('+1 month')) {
            $ligne = $index[$date->format('Y-m')] ?? ['vues' => 0, 'visiteurs' => 0];
            $points[] = ['date' => $date, 'jour' => $date->format('m/Y'), 'vues' => (int) $ligne['vues'], 'visiteurs' => (int) $ligne['visiteurs']];
        }

        return $points;
    }
}
