<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Query\GetAverageTimeToMergeInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlGetAverageTimeToMergeTest extends KernelTestCase
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var GetAverageTimeToMergeInterface */
    private $getAverageTimeToMerge;

    public function setUp(): void
    {
        parent::setUp();
        $this->getAverageTimeToMerge = $this->get('slub.infrastructure.persistence.get_average_time_to_merge');
        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->resetDB();
    }

    /**
     * @test
     */
    public function it_returns_null_if_there_is_no_pr()
    {
        self::assertNull($this->getAverageTimeToMerge->fetch());
    }

    /**
     * @test
     */
    public function it_returns_the_average_time_to_merge()
    {
        $this->addPRMergedInDay(2);
        $this->addPRMergedInDay(4);

        self::assertEquals(3, $this->getAverageTimeToMerge->fetch());
    }

    /**
     * @test
     */
    public function it_does_not_take_into_account_prs_created_prior_to_migration_Version20190609163730()
    {
        $this->createPRPriorToMigrationVersion20190609163730();
        self::assertNull($this->getAverageTimeToMerge->fetch());
    }

    /**
     * @test
     */
    public function it_does_not_take_into_account_prs_not_merged()
    {
        $this->createPRNotMerged();
        self::assertNull($this->getAverageTimeToMerge->fetch());
    }

    private function resetDB(): void
    {
        $sqlPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $sqlPRRepository->reset();
    }

    private function addPRMergedInDay(int $days): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $mergedAt = new \DateTime(sprintf('+ %d days', $days), new \DateTimeZone('UTC'));

        if (((string) $now->getTimestamp()) === ((string) $mergedAt->getTimestamp())) {
            throw new \Exception('yolo');
        }
        $this->PRRepository->save(
            PR::fromNormalized(
                [
                    'IDENTIFIER' => Uuid::uuid4()->toString(),
                    'AUTHOR_IDENTIFIER' => 'sam',
                    'TITLE' => 'Add new feature',
                    'GTMS' => 1,
                    'NOT_GTMS' => 1,
                    'COMMENTS' => 1,
                    'CI_STATUS' => [
                        'BUILD_RESULT' => 'PENDING',
                        'BUILD_LINK' => '',
                    ],
                    'IS_MERGED' => true,
                    'MESSAGE_IDS' => ['1', '2'],
                    'CHANNEL_IDS'       => ['squad-raccoons'],
                    'WORKSPACE_IDS' => ['akeneo'],
                    'PUT_TO_REVIEW_AT' => (string) $now->getTimestamp(),
                    'CLOSED_AT' => (string) $mergedAt->getTimestamp(),
                ]
            )
        );
    }

    private function createPRPriorToMigrationVersion20190609163730(): void
    {
        /** @var Connection $connection */
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $sql = <<<SQL
INSERT INTO `pr` (`IDENTIFIER`, `GTMS`, `NOT_GTMS`, `COMMENTS`, `CI_STATUS`, `IS_MERGED`, `MESSAGE_IDS`, `rows_before_migration_Version20190609163730`, `PUT_TO_REVIEW_AT`, `CLOSED_AT`, `AUTHOR_IDENTIFIER`, `TITLE`)
VALUES
	('pr_identifier', 3, 0, 1, '{"BUILD_RESULT": "PENDING", "BUILD_LINK": ""}', 1, '{}', 1, '251512', '251512', 'sam', 'Add new feature');
SQL;
        $connection->executeUpdate($sql);
    }

    private function createPRNotMerged(): void
    {
        /** @var Connection $connection */
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $sql = <<<SQL
INSERT INTO `pr` (`IDENTIFIER`, `GTMS`, `NOT_GTMS`, `COMMENTS`, `CI_STATUS`, `IS_MERGED`, `MESSAGE_IDS`, `rows_before_migration_Version20190609163730`, `PUT_TO_REVIEW_AT`, `CLOSED_AT`, `AUTHOR_IDENTIFIER`, `TITLE`)
VALUES
	('pr_identifier', 3, 0, 1, '{"BUILD_RESULT": "PENDING", "BUILD_LINK": ""}', 0, '{}', 0, '251512', '251512', 'sam', 'Add new feature');
SQL;
        $connection->executeUpdate($sql);
    }
}
