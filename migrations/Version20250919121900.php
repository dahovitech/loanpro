<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour le système de traduction multilingue
 */
final class Version20250919121900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du système de traduction multilingue avec les tables languages, services et service_translations';
    }

    public function up(Schema $schema): void
    {
        // Création de la table languages
        $this->addSql('CREATE TABLE languages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code VARCHAR(10) NOT NULL,
            name VARCHAR(100) NOT NULL,
            native_name VARCHAR(100) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            is_default BOOLEAN NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0
        )');
        
        $this->addSql('CREATE UNIQUE INDEX UNIQ_LANGUAGES_CODE ON languages (code)');

        // Création de la table services
        $this->addSql('CREATE TABLE services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug VARCHAR(255) NOT NULL,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )');
        
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SERVICES_SLUG ON services (slug)');

        // Création de la table service_translations
        $this->addSql('CREATE TABLE service_translations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_id INTEGER NOT NULL,
            language_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            detail TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
            FOREIGN KEY (language_id) REFERENCES languages (id) ON DELETE CASCADE
        )');
        
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SERVICE_TRANSLATIONS_SERVICE_LANGUAGE ON service_translations (service_id, language_id)');
        $this->addSql('CREATE INDEX IDX_SERVICE_TRANSLATIONS_SERVICE_ID ON service_translations (service_id)');
        $this->addSql('CREATE INDEX IDX_SERVICE_TRANSLATIONS_LANGUAGE_ID ON service_translations (language_id)');

        // Insertion des langues par défaut
        $this->addSql("INSERT INTO languages (code, name, native_name, is_active, is_default, sort_order) VALUES 
            ('fr', 'Français', 'Français', 1, 1, 1),
            ('en', 'English', 'English', 1, 0, 2),
            ('es', 'Español', 'Español', 1, 0, 3),
            ('de', 'Deutsch', 'Deutsch', 1, 0, 4)");
    }

    public function down(Schema $schema): void
    {
        // Suppression des tables dans l'ordre inverse
        $this->addSql('DROP TABLE service_translations');
        $this->addSql('DROP TABLE services');
        $this->addSql('DROP TABLE languages');
    }
}
