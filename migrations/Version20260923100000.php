<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260923100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cascade-delete a user\'s projects and discussion posts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A47E3C61F9');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A47E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discussion_posts DROP CONSTRAINT FK_47C241CFDD842E46');
        $this->addSql('ALTER TABLE discussion_posts ADD CONSTRAINT FK_47C241CFDD842E46 FOREIGN KEY (position_id) REFERENCES positions (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discussion_posts DROP CONSTRAINT FK_47C241CFF675F31B');
        $this->addSql('ALTER TABLE discussion_posts ADD CONSTRAINT FK_47C241CFF675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A47E3C61F9');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A47E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discussion_posts DROP CONSTRAINT FK_47C241CFDD842E46');
        $this->addSql('ALTER TABLE discussion_posts ADD CONSTRAINT FK_47C241CFDD842E46 FOREIGN KEY (position_id) REFERENCES positions (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discussion_posts DROP CONSTRAINT FK_47C241CFF675F31B');
        $this->addSql('ALTER TABLE discussion_posts ADD CONSTRAINT FK_47C241CFF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE');
    }
}
