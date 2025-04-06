<?php

declare(strict_types=1);

namespace Integration\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Driver\Connection;
use Slub\Infrastructure\Persistence\Sql\Repository\VCSEventRecorder;
use Tests\Integration\Infrastructure\KernelTestCase;

class VCSEventRecorderTest extends KernelTestCase
{
    private VCSEventRecorder $eventRecorder;
    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $this->eventRecorder = $this->get('slub.infrastructure.persistence.vcs_event_recorder');
        $this->eventRecorder->reset();
    }

    /**
     * @test
     */
    public function it_saves_a_pr_and_returns_it(): void
    {
        $check1 = 'ci_check1';
        $check2 = 'ci_check2';
        $repository1 = 'akeneo/akeneo-design-system';
        $repository2 = 'slub/slub';
        $type1 = 'ci_status';
        $type2 = 'check_run';

        $this->eventRecorder->recordEvent($repository1, $check1, $type1);
        $this->eventRecorder->recordEvent($repository1, $check1, $type1);
        $this->eventRecorder->recordEvent($repository1, $check1, $type1);

        $this->eventRecorder->recordEvent($repository2, $check2, $type2);

        $this->eventRecorder->recordEvent($repository2, $check1, $type1);

        $this->eventRecorder->recordEvent($repository2, $check2, $type1);

        $this->assertNumberOfCalls($repository1, $check1, $type1, 3);
        $this->assertNumberOfCalls($repository2, $check2, $type1, 1);
        $this->assertNumberOfCalls($repository2, $check1, $type1, 1);
        $this->assertNumberOfCalls($repository2, $check2, $type2, 1);
    }

    private function assertNumberOfCalls(string $repositoryIdentifier, string $eventName, string $eventType, int $expectedCount)
    {
        $sql = <<<SQL
SELECT NUMBER_OF_CALLS
FROM vcs_events_recordings
WHERE
 REPOSITORY_IDENTIFIER=:REPOSITORY_IDENTIFIER
 AND EVENT_NAME=:EVENT_NAME
 AND EVENT_TYPE=:EVENT_TYPE
;
SQL;
        $stmt = $this->connection->executeQuery(
            $sql,
            [
                'REPOSITORY_IDENTIFIER' => $repositoryIdentifier,
                'EVENT_NAME' => $eventName,
                'EVENT_TYPE' => $eventType,
            ]
        );
        $actualCount = current($stmt->fetchAll(\PDO::FETCH_COLUMN));

        $this->assertEquals($expectedCount, $actualCount);
    }
}
