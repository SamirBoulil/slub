<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add documents table to store documents (e.g. Notion) put to review via the /tr command';
    }

    public function up(Schema $schema): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS documents (
    IDENTIFIER VARCHAR(255) PRIMARY KEY,
    URL VARCHAR(1024) NOT NULL,
    AUTHOR_IDENTIFIER VARCHAR(255) NOT NULL,
    CHANNEL_IDS JSON,
    WORKSPACE_IDS JSON,
    MESSAGE_IDS JSON,
    PUT_TO_REVIEW_AT VARCHAR(20) NULL
);
SQL;
        $this->addSql($createTable);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE documents;');
    }
}
