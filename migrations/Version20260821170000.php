<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convertit les anciennes actualités Mods en fiches du catalogue sans casser leurs anciennes URL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO categorie_jeu (nom, slug) SELECT 'Mods', 'mods' WHERE NOT EXISTS (SELECT 1 FROM categorie_jeu WHERE slug = 'mods')");
        $this->addSql(<<<'SQL'
            INSERT INTO jeu (nom, slug, description, contenu, date_sortie, statut, miniature, banniere, cree_le, modifie_le, developpeur, categorie_id, createur_id, galerie, video_background, type_presentation)
            SELECT TRIM(REPLACE(a.titre, '[Mods]', '')), a.slug,
                   LEFT(CASE WHEN TRIM(a.description) = '' THEN TRIM(REPLACE(a.titre, '[Mods]', '')) ELSE a.description END, 160),
                   a.contenu, DATE(a.publiee_le), 'approuve', a.miniature, a.banniere,
                   a.publiee_le, a.publiee_le, NULL, c.id, a.auteur_id, JSON_ARRAY(), NULL, a.type_presentation
            FROM actualite a
            INNER JOIN categorie_jeu c ON c.slug = 'mods'
            WHERE a.categorie = 'mods'
              AND a.statut = 'publiee'
              AND NOT EXISTS (SELECT 1 FROM actualite_jeu aj WHERE aj.actualite_id = a.id)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO actualite_jeu (actualite_id, jeu_id)
            SELECT a.id, j.id
            FROM actualite a
            INNER JOIN jeu j ON j.slug = a.slug
            WHERE a.categorie = 'mods'
              AND NOT EXISTS (SELECT 1 FROM actualite_jeu aj WHERE aj.actualite_id = a.id AND aj.jeu_id = j.id)
        SQL);
    }

    public function postUp(Schema $schema): void
    {
        $correspondances = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT a.id AS actualite_id, j.id AS jeu_id, a.miniature, a.banniere
            FROM actualite a
            INNER JOIN actualite_jeu aj ON aj.actualite_id = a.id
            INNER JOIN jeu j ON j.id = aj.jeu_id
            WHERE a.categorie = 'mods'
        SQL);

        $racineProjet = dirname(__DIR__);
        foreach ($correspondances as $correspondance) {
            $this->copierMedias(
                $racineProjet.'/public/uploads/actualites/'.$correspondance['actualite_id'],
                $racineProjet.'/public/uploads/jeux/'.$correspondance['jeu_id'],
            );

            $commentaires = $this->connection->fetchAllAssociative(
                'SELECT id, auteur_id, contenu, date_commentaire, parent_id FROM commentaire_actualite WHERE actualite_id = ? ORDER BY id ASC',
                [$correspondance['actualite_id']],
            );
            $ids = [];
            foreach ($commentaires as $commentaire) {
                $this->connection->insert('commentaire_jeu', [
                    'jeu_id' => $correspondance['jeu_id'],
                    'auteur_id' => $commentaire['auteur_id'],
                    'contenu' => $commentaire['contenu'],
                    'date_commentaire' => $commentaire['date_commentaire'],
                    'parent_id' => $commentaire['parent_id'] === null ? null : ($ids[(int) $commentaire['parent_id']] ?? null),
                ]);
                $ids[(int) $commentaire['id']] = (int) $this->connection->lastInsertId();
            }

            foreach ($ids as $ancienId => $nouvelId) {
                $utilisateurs = $this->connection->fetchFirstColumn(
                    'SELECT utilisateur_id FROM commentaire_actualite_aime WHERE commentaire_actualite_id = ?',
                    [$ancienId],
                );
                foreach ($utilisateurs as $utilisateurId) {
                    $this->connection->insert('commentaire_jeu_aime', [
                        'commentaire_jeu_id' => $nouvelId,
                        'utilisateur_id' => $utilisateurId,
                    ]);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE cj FROM commentaire_jeu cj INNER JOIN jeu j ON j.id = cj.jeu_id INNER JOIN categorie_jeu c ON c.id = j.categorie_id WHERE c.slug = 'mods'");
        $this->addSql("DELETE j FROM jeu j INNER JOIN categorie_jeu c ON c.id = j.categorie_id WHERE c.slug = 'mods'");
        $this->addSql("DELETE FROM categorie_jeu WHERE slug = 'mods'");
    }

    private function copierMedias(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }
        if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier %s.', $destination));
        }
        foreach (new \FilesystemIterator($source) as $fichier) {
            if ($fichier->isFile()) {
                copy($fichier->getPathname(), $destination.'/'.$fichier->getFilename());
            }
        }
    }
}
