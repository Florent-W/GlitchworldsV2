<?php

namespace App\Command;

use App\Enum\CategorieActualite;
use App\Enum\StatutActualite;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(name: 'app:import-legacy-actualites', description: 'Importe les articles de l’ancien site en conservant leurs IDs et slugs.')]
final class ImportLegacyActualitesCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%env(default::LEGACY_DATABASE_URL)%')]
        private readonly ?string $legacyDatabaseUrl = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse sans écrire dans la base V2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->legacyDatabaseUrl) {
            $io->error('Variable LEGACY_DATABASE_URL manquante dans .env.local');
            return Command::FAILURE;
        }

        $legacy = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql', 'mysqli' => 'mysqli']))->parse($this->legacyDatabaseUrl));
        $articles = $legacy->fetchAllAssociative('SELECT id, titre, contenu, nom_categorie, nom_miniature, nom_banniere, date_creation, url, id_auteur, approuver, description FROM article ORDER BY id ASC');
        $commentaires = $legacy->fetchAllAssociative('SELECT id, id_utilisateur, contenu, id_news, date_commentaire FROM commentaire ORDER BY id ASC');
        $liaisonsJeux = $legacy->fetchAllAssociative('SELECT id_article, id_jeu FROM article_lier_jeu ORDER BY id_article, id_jeu');
        $mentionsJaime = $legacy->fetchAllAssociative('SELECT DISTINCT id_commentaire, id_pseudo_utilisateur_qui_aime AS id_utilisateur FROM aime_commentaire ORDER BY id_commentaire, id_pseudo_utilisateur_qui_aime');
        $slugger = new AsciiSlugger('fr');
        $slugs = [];
        $miniaturesDetectees = 0;
        $statuts = array_fill_keys(array_map(static fn (StatutActualite $statut): string => $statut->value, StatutActualite::cases()), 0);

        $this->connection->beginTransaction();
        try {
            foreach ($articles as $article) {
                $id = (int) $article['id'];
                $slugBase = strtolower($slugger->slug(trim((string) $article['url']) ?: (string) $article['titre'])->toString());
                $slug = $slugBase;
                if (isset($slugs[$slug])) {
                    $slug .= '-'.$id;
                }
                $slugs[$slug] = true;
                $statut = $this->convertirStatut((string) $article['approuver']);
                $miniature = $this->trouverMiniature($id, (string) $article['nom_miniature']);
                if ($miniature !== null && !str_starts_with($miniature, 'legacy:')) {
                    ++$miniaturesDetectees;
                }
                ++$statuts[$statut->value];

                if (!(bool) $input->getOption('dry-run')) {
                    $this->connection->executeStatement(
                        'INSERT INTO actualite (id, auteur_id, titre, slug, description, contenu, categorie, statut, miniature, banniere, publiee_le)
                         VALUES (:id, :auteur, :titre, :slug, :description, :contenu, :categorie, :statut, :miniature, :banniere, :publiee_le)
                         ON DUPLICATE KEY UPDATE auteur_id = VALUES(auteur_id), titre = VALUES(titre), slug = VALUES(slug), description = VALUES(description), contenu = VALUES(contenu), categorie = VALUES(categorie), statut = VALUES(statut), miniature = VALUES(miniature), banniere = VALUES(banniere), publiee_le = VALUES(publiee_le)',
                        [
                            'id' => $id,
                            'auteur' => $this->connection->fetchOne('SELECT id FROM utilisateur WHERE id = ?', [(int) $article['id_auteur']]) ?: null,
                            'titre' => trim((string) $article['titre']),
                            'slug' => $slug,
                            'description' => mb_substr(trim((string) $article['description']), 0, 160),
                            'contenu' => (string) $article['contenu'],
                            'categorie' => $this->convertirCategorie((string) $article['nom_categorie'])->value,
                            'statut' => $statut->value,
                            'miniature' => $miniature,
                            'banniere' => '' !== trim((string) ($article['nom_banniere'] ?? '')) ? 'legacy:'.trim((string) $article['nom_banniere']) : null,
                            'publiee_le' => (string) $article['date_creation'],
                        ],
                    );
                }
            }

            if (!(bool) $input->getOption('dry-run')) {
                foreach ($liaisonsJeux as $liaison) {
                    $this->connection->executeStatement(
                        'INSERT IGNORE INTO actualite_jeu (actualite_id, jeu_id) VALUES (:actualite, :jeu)',
                        ['actualite' => (int) $liaison['id_article'], 'jeu' => (int) $liaison['id_jeu']],
                    );
                }
                foreach ($commentaires as $commentaire) {
                    $this->connection->executeStatement(
                        'INSERT INTO commentaire_actualite (id, actualite_id, auteur_id, contenu, date_commentaire)
                         VALUES (:id, :actualite, :auteur, :contenu, :date)
                         ON DUPLICATE KEY UPDATE actualite_id = VALUES(actualite_id), auteur_id = VALUES(auteur_id), contenu = VALUES(contenu), date_commentaire = VALUES(date_commentaire)',
                        [
                            'id' => (int) $commentaire['id'],
                            'actualite' => (int) $commentaire['id_news'],
                            'auteur' => (int) $commentaire['id_utilisateur'],
                            'contenu' => (string) $commentaire['contenu'],
                            'date' => (string) $commentaire['date_commentaire'],
                        ],
                    );
                }
                foreach ($mentionsJaime as $mention) {
                    $this->connection->executeStatement(
                        'INSERT IGNORE INTO commentaire_actualite_aime (commentaire_actualite_id, utilisateur_id) VALUES (:commentaire, :utilisateur)',
                        ['commentaire' => (int) $mention['id_commentaire'], 'utilisateur' => (int) $mention['id_utilisateur']],
                    );
                }
            }

            (bool) $input->getOption('dry-run') ? $this->connection->rollBack() : $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $io->table(['Statut', 'Nombre'], [
            ['Publiées', $statuts[StatutActualite::Publiee->value]],
            ['En attente', $statuts[StatutActualite::EnAttente->value]],
            ['Brouillons', $statuts[StatutActualite::Brouillon->value]],
        ]);
        $io->writeln(sprintf('Miniatures déjà présentes dans la V2 : %d', $miniaturesDetectees));
        $io->writeln(sprintf('Commentaires associés : %d', \count($commentaires)));
        $io->writeln(sprintf('Liaisons avec des jeux : %d', \count($liaisonsJeux)));
        $io->writeln(sprintf('Mentions J’aime uniques : %d', \count($mentionsJaime)));
        $io->success(sprintf('%d actualités %s.', \count($articles), (bool) $input->getOption('dry-run') ? 'analysées' : 'importées'));

        return Command::SUCCESS;
    }

    private function convertirStatut(string $statut): StatutActualite
    {
        $statut = mb_strtolower(trim($statut));
        if ('approuver' === $statut) {
            return StatutActualite::Publiee;
        }

        return str_contains($statut, 'brouillon') ? StatutActualite::Brouillon : StatutActualite::EnAttente;
    }

    private function convertirCategorie(string $categorie): CategorieActualite
    {
        return CategorieActualite::tryFrom(mb_strtolower(trim($categorie))) ?? CategorieActualite::News;
    }

    private function trouverMiniature(int $actualiteId, string $nomLegacy): ?string
    {
        $dossier = $this->projectDir.'/public/uploads/actualites/'.$actualiteId;
        foreach (['webp', 'png', 'jpg', 'jpeg'] as $extension) {
            $nom = 'miniature.'.$extension;
            if (is_file($dossier.'/'.$nom)) {
                return $nom;
            }
        }

        $nomLegacy = trim($nomLegacy);

        return '' !== $nomLegacy ? 'legacy:'.$nomLegacy : null;
    }
}
