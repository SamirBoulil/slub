<?php

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Driver\Connection;

class VCSEventRecorder
{
    public function __construct(private Connection $connection, private bool $shouldRecordEvents) {}
    public function recordEvent(string $repositoryIdentifier, string $eventName, string $eventType): void
    {
        if(!$this->shouldRecordEvents){
            return;
        }

        $sql = <<<SQL
INSERT INTO
  vcs_events_recordings (REPOSITORY_IDENTIFIER, EVENT_NAME, EVENT_TYPE, NUMBER_OF_CALLS)
VALUES
  (:REPOSITORY_IDENTIFIER, :EVENT_NAME, :EVENT_TYPE, 1)
ON DUPLICATE KEY UPDATE
  NUMBER_OF_CALLS = NUMBER_OF_CALLS + 1
  ;
SQL;

        $this->connection->executeStatement(
            $sql,
            [
                'REPOSITORY_IDENTIFIER' => $repositoryIdentifier,
                'EVENT_NAME' => $eventName,
                'EVENT_TYPE' => $eventType,
            ]
        );
    }

    public function reset()
    {
        $this->connection->executeStatement('DELETE FROM vcs_events_recordings;');
    }
}
