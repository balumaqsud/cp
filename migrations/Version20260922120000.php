<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260922120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow null passwords for social-only accounts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ALTER password DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE users SET password = '' WHERE password IS NULL");
        $this->addSql('ALTER TABLE users ALTER password SET NOT NULL');
    }
}
