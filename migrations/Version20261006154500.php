<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006154500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a nullable unique API token on positions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE positions ADD api_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D69FE57C7BA2F5EB ON positions (api_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D69FE57C7BA2F5EB');
        $this->addSql('ALTER TABLE positions DROP api_token');
    }
}
