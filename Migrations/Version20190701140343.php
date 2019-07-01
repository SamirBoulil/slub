<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190701140343 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds the list of channel identifiers a PR has been published to';
    }

    public function up(Schema $schema) : void
    {
        if ($this->environmentIsMigrated()) {
            return;
        }
        $this->markRowsToBeMigrated();
        $this->addChannelIdsColumn();
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE pr DROP rows_before_migration_Version20190701140343;');
        $this->addSql('ALTER TABLE pr DROP CHANNEL_IDS;');
    }

    private function addChannelIdsColumn(): void
    {
        $this->addSql('ALTER TABLE pr ADD CHANNEL_IDS JSON NULL;');
    }

    private function environmentIsMigrated(): bool
    {
        $columns = $this->fetchColumns();

        return in_array('CHANNEL_IDS', $columns);
    }

    private function fetchColumns(): array
    {
        $getPRColumns = 'SHOW COLUMNS FROM pr;';
        $result = $this->connection->executeQuery($getPRColumns);

        return $result->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function markRowsToBeMigrated(): void
    {
        $this->addSql('ALTER TABLE pr ADD rows_before_migration_Version20190701140343 BOOL NOT NULL DEFAULT FALSE;');
        $this->addSql(
            'UPDATE pr SET rows_before_migration_Version20190701140343 = TRUE WHERE rows_before_migration_Version20190701140343 = FALSE;'
        );
    }
}
