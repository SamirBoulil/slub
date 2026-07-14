<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710130000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table to cache Github API responses with their ETag so they can be revalidated with conditional requests that do not count against the rate limit';
    }

    public function up(Schema $schema): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS github_api_response_cache (
    URL_HASH CHAR(64) PRIMARY KEY,
    URL TEXT NOT NULL,
    ETAG VARCHAR(255) NOT NULL,
    RESPONSE_BODY MEDIUMTEXT NOT NULL,
    REFRESHED_AT DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL;
        $this->addSql($createTable);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE github_api_response_cache;');
    }
}
