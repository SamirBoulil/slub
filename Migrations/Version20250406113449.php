<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250406113449 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table to track events usage per repository';
    }

    public function up(Schema $schema): void
    {
        $this->addStatsTable();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vcs_events_recordings;');
    }

    private function addStatsTable(): void
    {
        $createTable = <<<SQL
CREATE TABLE IF NOT EXISTS vcs_events_recordings (
    REPOSITORY_IDENTIFIER VARCHAR(255),
    EVENT_NAME VARCHAR(255),
    EVENT_TYPE VARCHAR(255),
    NUMBER_OF_CALLS INT,
    PRIMARY KEY(REPOSITORY_IDENTIFIER, EVENT_NAME, EVENT_TYPE)
);
SQL;
        $this->addSql($createTable);
    }
}
