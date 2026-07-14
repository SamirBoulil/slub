<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table to associate commits to the PR they belong to, so status events do not need the Github API to resolve PR numbers';
    }

    public function up(Schema $schema): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS pr_commits (
    REPOSITORY_IDENTIFIER VARCHAR(255) NOT NULL,
    COMMIT_SHA VARCHAR(64) NOT NULL,
    PR_NUMBER VARCHAR(255) NULL,
    CREATED_AT DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(REPOSITORY_IDENTIFIER, COMMIT_SHA)
);
SQL;
        $this->addSql($createTable);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pr_commits;');
    }
}
