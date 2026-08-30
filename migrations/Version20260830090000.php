<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Harmonise les noms des succès et clarifie leur progression.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE succes
            SET nom = CASE code
                WHEN 'collectionneur_5' THEN 'Première étagère'
                WHEN 'niveau_5' THEN 'Premiers repères'
                WHEN 'niveau_50' THEN 'Légende de Glitchworlds'
                WHEN 'fan_10' THEN 'Passionné'
                WHEN 'fan_25' THEN 'Cœur de collection'
                WHEN 'critique_5' THEN 'Œil critique'
                WHEN 'bavard_25' THEN 'Au cœur des discussions'
                WHEN 'bavard_50' THEN 'Pilier des échanges'
                WHEN 'chroniqueur_25' THEN 'Chroniqueur incontournable'
                WHEN 'premier_suivi' THEN 'Premier contact'
                WHEN 'curateur_5' THEN 'Bibliothécaire'
                WHEN 'portrait' THEN 'Visage découvert'
                WHEN 'premiere_banniere' THEN 'Profil en couleurs'
                WHEN 'premier_achat' THEN 'Passage en boutique'
                WHEN 'premier_message' THEN 'Correspondant'
                ELSE nom
            END
            WHERE code IN (
                'collectionneur_5',
                'niveau_5',
                'niveau_50',
                'fan_10',
                'fan_25',
                'critique_5',
                'bavard_25',
                'bavard_50',
                'chroniqueur_25',
                'premier_suivi',
                'curateur_5',
                'portrait',
                'premiere_banniere',
                'premier_achat',
                'premier_message'
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE succes
            SET nom = CASE code
                WHEN 'collectionneur_5' THEN 'Collectionneur'
                WHEN 'niveau_5' THEN 'Habitué de Glitchworlds'
                WHEN 'niveau_50' THEN 'Légende Glitchworlds'
                WHEN 'fan_10' THEN 'Super fan'
                WHEN 'fan_25' THEN 'Cœur à prendre'
                WHEN 'critique_5' THEN 'Critique affirmé'
                WHEN 'bavard_25' THEN 'Habitué du fil'
                WHEN 'bavard_50' THEN 'Toujours en ligne'
                WHEN 'chroniqueur_25' THEN 'Chroniqueur'
                WHEN 'premier_suivi' THEN 'Curieux'
                WHEN 'curateur_5' THEN 'Curateur'
                WHEN 'portrait' THEN 'Portrait'
                WHEN 'premiere_banniere' THEN 'Devanture'
                WHEN 'premier_achat' THEN 'Premier échange'
                WHEN 'premier_message' THEN 'Facteur'
                ELSE nom
            END
            WHERE code IN (
                'collectionneur_5',
                'niveau_5',
                'niveau_50',
                'fan_10',
                'fan_25',
                'critique_5',
                'bavard_25',
                'bavard_50',
                'chroniqueur_25',
                'premier_suivi',
                'curateur_5',
                'portrait',
                'premiere_banniere',
                'premier_achat',
                'premier_message'
            )
            SQL);
    }
}
