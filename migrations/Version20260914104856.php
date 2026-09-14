<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914104856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed built-in attributes: First Name, Last Name, Location, Photo.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO attributes (name, description, type, is_built_in, options, version, category_id)
            SELECT 'First Name', NULL, 'string', true, '[]'::json, NULL, c.id
            FROM attribute_categories c
            WHERE c.name = 'Personal Information'
              AND NOT EXISTS (SELECT 1 FROM attributes a WHERE a.name = 'First Name')
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO attributes (name, description, type, is_built_in, options, version, category_id)
            SELECT 'Last Name', NULL, 'string', true, '[]'::json, NULL, c.id
            FROM attribute_categories c
            WHERE c.name = 'Personal Information'
              AND NOT EXISTS (SELECT 1 FROM attributes a WHERE a.name = 'Last Name')
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO attributes (name, description, type, is_built_in, options, version, category_id)
            SELECT 'Location', NULL, 'string', true, '[]'::json, NULL, c.id
            FROM attribute_categories c
            WHERE c.name = 'Personal Information'
              AND NOT EXISTS (SELECT 1 FROM attributes a WHERE a.name = 'Location')
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO attributes (name, description, type, is_built_in, options, version, category_id)
            SELECT 'Photo', NULL, 'image', true, '[]'::json, NULL, c.id
            FROM attribute_categories c
            WHERE c.name = 'Personal Information'
              AND NOT EXISTS (SELECT 1 FROM attributes a WHERE a.name = 'Photo')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM attributes WHERE name IN ('First Name', 'Last Name', 'Location', 'Photo', 'Personal Photo') AND is_built_in = true");
    }
}
