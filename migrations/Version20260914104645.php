<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260914104645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE positions ADD is_public BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE positions ADD project_tags JSON NOT NULL');
        $this->addSql('ALTER TABLE positions ADD max_projects INT DEFAULT NULL');
        $this->addSql('ALTER TABLE positions ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE positions ADD company VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE positions ADD level VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE positions DROP is_public');
        $this->addSql('ALTER TABLE positions DROP project_tags');
        $this->addSql('ALTER TABLE positions DROP max_projects');
        $this->addSql('ALTER TABLE positions DROP version');
        $this->addSql('ALTER TABLE positions DROP company');
        $this->addSql('ALTER TABLE positions DROP level');
    }
}
