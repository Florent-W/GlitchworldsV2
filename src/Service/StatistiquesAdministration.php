<?php
namespace App\Service;
use Doctrine\DBAL\Connection;
final class StatistiquesAdministration
{
    public function __construct(private Connection $db) {}
    public function construire(int $jours): array
    {
        $jours = in_array($jours, [7, 30, 90], true) ? $jours : 30;
        $debut = (new \DateTimeImmutable('today'))->modify('-'.($jours - 1).' days');
        $vues = $this->db->fetchAllAssociative('SELECT DATE(vue_le) jour, COUNT(*) vues, COUNT(DISTINCT visiteur_hash) visiteurs FROM vue_page WHERE vue_le >= :debut GROUP BY DATE(vue_le)', ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $index = []; foreach ($vues as $ligne) { $index[$ligne['jour']] = $ligne; }
        $graphique = []; $maximum = 1;
        for ($i = 0; $i < $jours; ++$i) { $date = $debut->modify('+'.$i.' days'); $ligne = $index[$date->format('Y-m-d')] ?? ['vues' => 0, 'visiteurs' => 0]; $maximum = max($maximum, (int) $ligne['vues'], (int) $ligne['visiteurs']); $graphique[] = ['jour' => $date->format($jours <= 7 ? 'D' : 'd/m'), 'vues' => (int) $ligne['vues'], 'visiteurs' => (int) $ligne['visiteurs']]; }
        $totaux = $this->db->fetchAssociative('SELECT COUNT(*) vues, COUNT(DISTINCT visiteur_hash) visiteurs FROM vue_page WHERE vue_le >= :debut', ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $jeux = $this->db->fetchAllAssociative("SELECT j.id, j.nom titre, j.slug, COUNT(v.id) vues, COUNT(DISTINCT v.visiteur_hash) visiteurs FROM jeu j LEFT JOIN vue_page v ON v.type_contenu = 'jeu' AND v.contenu_id = j.id AND v.vue_le >= :debut GROUP BY j.id, j.nom, j.slug ORDER BY vues DESC LIMIT 10", ['debut' => $debut->format('Y-m-d 00:00:00')]);
        $actualites = $this->db->fetchAllAssociative("SELECT a.id, a.titre, a.slug, COUNT(v.id) vues, COUNT(DISTINCT v.visiteur_hash) visiteurs FROM actualite a LEFT JOIN vue_page v ON v.type_contenu = 'actualite' AND v.contenu_id = a.id AND v.vue_le >= :debut GROUP BY a.id, a.titre, a.slug ORDER BY vues DESC LIMIT 10", ['debut' => $debut->format('Y-m-d 00:00:00')]);
        return ['jours' => $jours, 'graphique' => $graphique, 'maximum' => $maximum, 'vues' => (int) ($totaux['vues'] ?? 0), 'visiteurs' => (int) ($totaux['visiteurs'] ?? 0), 'jeux' => $jeux, 'actualites' => $actualites];
    }
}
