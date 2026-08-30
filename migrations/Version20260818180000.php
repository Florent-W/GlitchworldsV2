<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les préférences de compte pour le thème, les notifications, l’accessibilité et la confidentialité.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD theme VARCHAR(32) DEFAULT "system" NOT NULL, ADD reduction_animations TINYINT(1) DEFAULT 0 NOT NULL, ADD notifications JSON DEFAULT ("[]") NOT NULL, ADD profil_prive TINYINT(1) DEFAULT 0 NOT NULL, ADD contraste_renforce TINYINT(1) DEFAULT 0 NOT NULL, ADD taille_texte VARCHAR(16) DEFAULT "normal" NOT NULL, ADD sessions_connectees JSON DEFAULT ("[]") NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP theme, DROP reduction_animations, DROP notifications, DROP profil_prive, DROP contraste_renforce, DROP taille_texte, DROP sessions_connectees');
    }
}
