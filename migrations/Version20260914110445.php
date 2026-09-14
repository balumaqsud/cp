<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914110445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_required; optimistic lock versions; rename Photo to Personal Photo.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE attributes SET version = 1 WHERE version IS NULL');
        $this->addSql('ALTER TABLE attributes ALTER version SET DEFAULT 1');
        $this->addSql('ALTER TABLE attributes ALTER version SET NOT NULL');

        $this->addSql('ALTER TABLE attribute_values ADD version INT DEFAULT 1 NOT NULL');

        $this->addSql('ALTER TABLE position_attributes ADD is_required BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE position_attributes ALTER is_required DROP DEFAULT');

        $this->addSql("UPDATE attributes SET name = 'Personal Photo' WHERE name = 'Photo' AND is_built_in = true");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE attributes SET name = 'Photo' WHERE name = 'Personal Photo' AND is_built_in = true");

        $this->addSql('ALTER TABLE position_attributes DROP is_required');
        $this->addSql('ALTER TABLE attribute_values DROP version');
        $this->addSql('ALTER TABLE attributes ALTER version DROP DEFAULT');
        $this->addSql('ALTER TABLE attributes ALTER version DROP NOT NULL');
    }
}
