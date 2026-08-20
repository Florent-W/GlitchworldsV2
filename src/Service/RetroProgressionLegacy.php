<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Enum\StatutJeu;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RetroProgressionLegacy
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProgressionUtilisateur $progression,
        private readonly GestionSucces $gestionSucces,
        #[Autowire('%env(default::LEGACY_DATABASE_URL)%')]
        private readonly ?string $legacyDatabaseUrl = null,
    ) {
    }

    /**
     * @return array{avis: int, createurs: int}
     */
    public function synchroniserDonneesLegacy(): array
    {
        if (!$this->legacyDatabaseUrl) {
            return ['avis' => 0, 'createurs' => 0];
        }

        $legacy = DriverManager::getConnection((new DsnParser([
            'mysql' => 'pdo_mysql',
            'mysqli' => 'mysqli',
        ]))->parse($this->legacyDatabaseUrl));

        $avisCorriges = 0;
        foreach ($legacy->fetchAllAssociative('SELECT id, id_utilisateur FROM avis WHERE id_utilisateur IS NOT NULL') as $avis) {
            $auteurId = (int) $avis['id_utilisateur'];
            if (!(bool) $this->connection->fetchOne('SELECT 1 FROM utilisateur WHERE id = ?', [$auteurId])) {
                continue;
            }

            $avisCorriges += $this->connection->executeStatement(
                'UPDATE avis SET auteur_id = ? WHERE id = ? AND (auteur_id IS NULL OR auteur_id = 0)',
                [$auteurId, (int) $avis['id']],
            );
        }

        $createursCorriges = 0;
        foreach ($legacy->fetchAllAssociative('SELECT id, id_auteur_presentation FROM jeu WHERE id_auteur_presentation IS NOT NULL') as $jeu) {
            $createurId = (int) $jeu['id_auteur_presentation'];
            if (!(bool) $this->connection->fetchOne('SELECT 1 FROM utilisateur WHERE id = ?', [$createurId])) {
                continue;
            }

            $createursCorriges += $this->connection->executeStatement(
                'UPDATE jeu SET createur_id = ? WHERE id = ? AND createur_id IS NULL',
                [$createurId, (int) $jeu['id']],
            );
        }

        return ['avis' => $avisCorriges, 'createurs' => $createursCorriges];
    }

    /**
     * @return array<string, int>
     */
    public function attribuer(?int $utilisateurId = null, bool $dryRun = false, bool $notifier = false): array
    {
        $stats = [
            'membres' => 0,
            'commentaires_jeu' => 0,
            'commentaires_actualite' => 0,
            'notes' => 0,
            'favoris' => 0,
            'publications' => 0,
            'jeux_approuves' => 0,
            'anciennete' => 0,
            'succes' => 0,
            'experience' => 0,
            'points' => 0,
        ];

        $ids = $utilisateurId !== null
            ? [$utilisateurId]
            : array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM utilisateur ORDER BY id ASC'));

        foreach ($ids as $id) {
            $utilisateur = $this->entityManager->find(Utilisateur::class, $id);
            if (!$utilisateur instanceof Utilisateur) {
                continue;
            }

            ++$stats['membres'];
            $avantExperience = $utilisateur->getExperience();
            $avantPoints = $utilisateur->getPoints();

            if (!$dryRun) {
                $stats['commentaires_jeu'] += $this->crediterCommentairesJeu($utilisateur);
                $stats['commentaires_actualite'] += $this->crediterCommentairesActualite($utilisateur);
                $stats['notes'] += $this->crediterNotes($utilisateur);
                $stats['favoris'] += $this->crediterFavoris($utilisateur);
                $stats['publications'] += $this->crediterPublications($utilisateur);
                $stats['jeux_approuves'] += $this->crediterJeuxApprouves($utilisateur);
                if ($this->progression->recompenseAnciennete($utilisateur)) {
                    ++$stats['anciennete'];
                }
                $stats['succes'] += \count($this->gestionSucces->verifier($utilisateur, $notifier));
                $this->entityManager->flush();
                $stats['experience'] += $utilisateur->getExperience() - $avantExperience;
                $stats['points'] += $utilisateur->getPoints() - $avantPoints;
                $this->entityManager->clear();
            } else {
                $commentairesJeu = $this->compterCommentairesJeu($id);
                $commentairesActualite = $this->compterCommentairesActualite($id);
                $notes = $this->compterNotes($id);
                $favoris = $this->compterFavoris($id);
                $publications = $this->compterPublications($id);
                $jeuxApprouves = $this->compterJeuxApprouves($id);
                $anciennete = $utilisateur->getInscritLe()->diff(new \DateTimeImmutable())->y > 0 ? 1 : 0;

                $stats['commentaires_jeu'] += $commentairesJeu;
                $stats['commentaires_actualite'] += $commentairesActualite;
                $stats['notes'] += $notes;
                $stats['favoris'] += $favoris;
                $stats['publications'] += $publications;
                $stats['jeux_approuves'] += $jeuxApprouves;
                $stats['anciennete'] += $anciennete;
                $stats['experience'] += ($commentairesJeu + $commentairesActualite) * ProgressionUtilisateur::COMMENTAIRE_XP
                    + $notes * 5
                    + $favoris * 3
                    + $publications * ProgressionUtilisateur::PUBLICATION_XP
                    + $jeuxApprouves * ProgressionUtilisateur::JEU_APPROUVE_XP
                    + $anciennete * 25;
                $stats['points'] += ($commentairesJeu + $commentairesActualite) * ProgressionUtilisateur::COMMENTAIRE_POINTS
                    + $notes * 2
                    + $favoris
                    + $publications * ProgressionUtilisateur::PUBLICATION_POINTS
                    + $jeuxApprouves * ProgressionUtilisateur::JEU_APPROUVE_POINTS
                    + $anciennete * 10;
            }
        }

        return $stats;
    }

    private function crediterCommentairesJeu(Utilisateur $utilisateur): int
    {
        $credites = 0;
        foreach ($this->connection->fetchFirstColumn(
            'SELECT id FROM commentaire_jeu WHERE auteur_id = ? ORDER BY id ASC',
            [$utilisateur->getId()],
        ) as $commentaireId) {
            if ($this->progression->crediterHistorique(
                $utilisateur,
                'commentaire',
                'commentaire-jeu:'.$commentaireId,
                'Commentaire publié',
                ProgressionUtilisateur::COMMENTAIRE_XP,
                ProgressionUtilisateur::COMMENTAIRE_POINTS,
            )) {
                ++$credites;
            }
        }

        return $credites;
    }

    private function crediterCommentairesActualite(Utilisateur $utilisateur): int
    {
        $credites = 0;
        foreach ($this->connection->fetchFirstColumn(
            'SELECT id FROM commentaire_actualite WHERE auteur_id = ? ORDER BY id ASC',
            [$utilisateur->getId()],
        ) as $commentaireId) {
            if ($this->progression->crediterHistorique(
                $utilisateur,
                'commentaire',
                'commentaire-actualite:'.$commentaireId,
                'Commentaire publié',
                ProgressionUtilisateur::COMMENTAIRE_XP,
                ProgressionUtilisateur::COMMENTAIRE_POINTS,
            )) {
                ++$credites;
            }
        }

        return $credites;
    }

    private function crediterNotes(Utilisateur $utilisateur): int
    {
        $credites = 0;
        foreach ($this->connection->fetchFirstColumn(
            'SELECT jeu_id FROM avis WHERE auteur_id = ? ORDER BY jeu_id ASC',
            [$utilisateur->getId()],
        ) as $jeuId) {
            if ($this->progression->crediterHistorique(
                $utilisateur,
                'note',
                'note:'.$jeuId,
                'Première note sur un jeu',
                5,
                2,
            )) {
                ++$credites;
            }
        }

        return $credites;
    }

    private function crediterFavoris(Utilisateur $utilisateur): int
    {
        $credites = 0;
        foreach ($this->connection->fetchFirstColumn(
            'SELECT jeu_id FROM utilisateur_jeu_favori WHERE utilisateur_id = ? ORDER BY jeu_id ASC',
            [$utilisateur->getId()],
        ) as $jeuId) {
            if ($this->progression->crediterHistorique(
                $utilisateur,
                'favori',
                'favori:'.$jeuId,
                'Premier ajout d’un jeu aux favoris',
                3,
                1,
            )) {
                ++$credites;
            }
        }

        return $credites;
    }

    private function crediterPublications(Utilisateur $utilisateur): int
    {
        $credites = 0;
        foreach ($this->connection->fetchFirstColumn(
            'SELECT id FROM publication WHERE auteur_id = ? ORDER BY id ASC',
            [$utilisateur->getId()],
        ) as $publicationId) {
            if ($this->progression->crediterHistorique(
                $utilisateur,
                'publication',
                'publication:'.$publicationId,
                'Publication communautaire',
                ProgressionUtilisateur::PUBLICATION_XP,
                ProgressionUtilisateur::PUBLICATION_POINTS,
            )) {
                ++$credites;
            }
        }

        return $credites;
    }

    private function crediterJeuxApprouves(Utilisateur $utilisateur): int
    {
        $credites = 0;
        foreach ($this->connection->fetchFirstColumn(
            'SELECT id FROM jeu WHERE createur_id = ? AND statut = ? ORDER BY id ASC',
            [$utilisateur->getId(), StatutJeu::Approuve->value],
        ) as $jeuId) {
            if ($this->progression->crediterHistorique(
                $utilisateur,
                'jeu_approuve',
                'jeu-approuve:'.$jeuId,
                'Jeu approuvé',
                ProgressionUtilisateur::JEU_APPROUVE_XP,
                ProgressionUtilisateur::JEU_APPROUVE_POINTS,
            )) {
                ++$credites;
            }
        }

        return $credites;
    }

    private function compterCommentairesJeu(int $utilisateurId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM commentaire_jeu WHERE auteur_id = ?',
            [$utilisateurId],
        );
    }

    private function compterCommentairesActualite(int $utilisateurId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM commentaire_actualite WHERE auteur_id = ?',
            [$utilisateurId],
        );
    }

    private function compterNotes(int $utilisateurId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM avis WHERE auteur_id = ?',
            [$utilisateurId],
        );
    }

    private function compterFavoris(int $utilisateurId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM utilisateur_jeu_favori WHERE utilisateur_id = ?',
            [$utilisateurId],
        );
    }

    private function compterPublications(int $utilisateurId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM publication WHERE auteur_id = ?',
            [$utilisateurId],
        );
    }

    private function compterJeuxApprouves(int $utilisateurId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM jeu WHERE createur_id = ? AND statut = ?',
            [$utilisateurId, StatutJeu::Approuve->value],
        );
    }
}
