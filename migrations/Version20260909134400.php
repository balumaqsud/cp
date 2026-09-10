<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909134400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop default on projects.technology_tags so the column matches Doctrine mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects ALTER technology_tags DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE projects ALTER technology_tags SET DEFAULT '[]'::json");
    }
}
