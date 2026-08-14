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
        $articles = $legacy->fetchAllAssociative('SELECT id, titre, contenu, nom_categorie, nom_miniature, date_creation, url, id_auteur, approuver, description FROM article ORDER BY id ASC');
        $slugger = new AsciiSlugger('fr');
        $slugs = [];
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
                ++$statuts[$statut->value];

                if (!(bool) $input->getOption('dry-run')) {
                    $this->connection->executeStatement(
                        'INSERT INTO actualite (id, auteur_id, titre, slug, description, contenu, categorie, statut, miniature, publiee_le)
                         VALUES (:id, :auteur, :titre, :slug, :description, :contenu, :categorie, :statut, :miniature, :publiee_le)
                         ON DUPLICATE KEY UPDATE auteur_id = VALUES(auteur_id), titre = VALUES(titre), slug = VALUES(slug), description = VALUES(description), contenu = VALUES(contenu), categorie = VALUES(categorie), statut = VALUES(statut), miniature = VALUES(miniature), publiee_le = VALUES(publiee_le)',
                        [
                            'id' => $id,
                            'auteur' => $this->connection->fetchOne('SELECT id FROM utilisateur WHERE id = ?', [(int) $article['id_auteur']]) ?: null,
                            'titre' => trim((string) $article['titre']),
                            'slug' => $slug,
                            'description' => mb_substr(trim((string) $article['description']), 0, 160),
                            'contenu' => (string) $article['contenu'],
                            'categorie' => $this->convertirCategorie((string) $article['nom_categorie'])->value,
                            'statut' => $statut->value,
                            'miniature' => trim((string) $article['nom_miniature']) ?: null,
                            'publiee_le' => (string) $article['date_creation'],
                        ],
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
}
