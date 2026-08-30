<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Distingue la fiche propre du mod des jeux simplement associés à son ancien article.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite ADD fiche_jeu_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE actualite ADD CONSTRAINT FK_ACTUALITE_FICHE_JEU FOREIGN KEY (fiche_jeu_id) REFERENCES jeu (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_ACTUALITE_FICHE_JEU ON actualite (fiche_jeu_id)');
        $this->addSql(<<<'SQL'
            INSERT INTO jeu (nom, slug, description, contenu, date_sortie, statut, miniature, banniere, cree_le, modifie_le, developpeur, categorie_id, createur_id, galerie, video_background, type_presentation)
            SELECT TRIM(REPLACE(a.titre, '[Mods]', '')), a.slug,
                   LEFT(CASE WHEN TRIM(a.description) = '' THEN TRIM(REPLACE(a.titre, '[Mods]', '')) ELSE a.description END, 160),
                   a.contenu, DATE(a.publiee_le), 'approuve', a.miniature, a.banniere,
                   a.publiee_le, a.publiee_le, NULL, c.id, a.auteur_id, JSON_ARRAY(), NULL, a.type_presentation
            FROM actualite a
            INNER JOIN categorie_jeu c ON c.slug = 'mods'
            WHERE a.categorie = 'mods' AND a.statut = 'publiee'
              AND NOT EXISTS (SELECT 1 FROM jeu j WHERE j.slug = a.slug)
        SQL);
        $this->addSql("UPDATE actualite a INNER JOIN jeu j ON j.slug = a.slug INNER JOIN categorie_jeu c ON c.id = j.categorie_id AND c.slug = 'mods' SET a.fiche_jeu_id = j.id WHERE a.categorie = 'mods'");
    }

    public function postUp(Schema $schema): void
    {
        $racineProjet = dirname(__DIR__);
        $conversions = $this->connection->fetchAllAssociative("SELECT id, fiche_jeu_id FROM actualite WHERE categorie = 'mods' AND fiche_jeu_id IS NOT NULL");

        foreach ($conversions as $conversion) {
            $source = $racineProjet.'/public/uploads/actualites/'.$conversion['id'];
            $destination = $racineProjet.'/public/uploads/jeux/'.$conversion['fiche_jeu_id'];
            if (is_dir($source)) {
                if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
                    throw new \RuntimeException(sprintf('Impossible de créer le dossier %s.', $destination));
                }
                foreach (new \FilesystemIterator($source) as $fichier) {
                    if ($fichier->isFile()) {
                        copy($fichier->getPathname(), $destination.'/'.$fichier->getFilename());
                    }
                }
            }

            $commentaires = $this->connection->fetchAllAssociative(
                'SELECT id, auteur_id, contenu, date_commentaire, parent_id FROM commentaire_actualite WHERE actualite_id = ? ORDER BY id ASC',
                [$conversion['id']],
            );
            $ids = [];
            foreach ($commentaires as $commentaire) {
                $existant = $this->connection->fetchOne(
                    'SELECT id FROM commentaire_jeu WHERE jeu_id = ? AND contenu = ? AND date_commentaire = ? AND (auteur_id = ? OR (auteur_id IS NULL AND ? IS NULL)) LIMIT 1',
                    [$conversion['fiche_jeu_id'], $commentaire['contenu'], $commentaire['date_commentaire'], $commentaire['auteur_id'], $commentaire['auteur_id']],
                );
                if ($existant !== false) {
                    $ids[(int) $commentaire['id']] = (int) $existant;
                    continue;
                }
                $this->connection->insert('commentaire_jeu', [
                    'jeu_id' => $conversion['fiche_jeu_id'],
                    'auteur_id' => $commentaire['auteur_id'],
                    'contenu' => $commentaire['contenu'],
                    'date_commentaire' => $commentaire['date_commentaire'],
                    'parent_id' => $commentaire['parent_id'] === null ? null : ($ids[(int) $commentaire['parent_id']] ?? null),
                ]);
                $ids[(int) $commentaire['id']] = (int) $this->connection->lastInsertId();
            }

            foreach ($ids as $ancienId => $nouvelId) {
                foreach ($this->connection->fetchFirstColumn('SELECT utilisateur_id FROM commentaire_actualite_aime WHERE commentaire_actualite_id = ?', [$ancienId]) as $utilisateurId) {
                    $this->connection->executeStatement(
                        'INSERT IGNORE INTO commentaire_jeu_aime (commentaire_jeu_id, utilisateur_id) VALUES (?, ?)',
                        [$nouvelId, $utilisateurId],
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite DROP FOREIGN KEY FK_ACTUALITE_FICHE_JEU');
        $this->addSql('DROP INDEX IDX_ACTUALITE_FICHE_JEU ON actualite');
        $this->addSql('ALTER TABLE actualite DROP fiche_jeu_id');
    }
}
