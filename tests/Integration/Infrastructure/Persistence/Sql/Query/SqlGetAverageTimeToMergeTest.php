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
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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
     *      It works with PR previous to Version20190609163730
     */
    public function it_does_not_take_into_account_pr_without_a_put_to_review_at_date()
    {
        $this->createPRWithoutPutToReviewDate();
        self::assertNull($this->getAverageTimeToMerge->fetch());
    }

    /**
     * @test
     *      It works with PR previous to Version20190609163730
     */
    public function it_does_not_take_into_account_pr_without_a_merged_at_date()
    {
        $this->createPRWithoutMergedAtDate();
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
                    'IDENTIFIER'       => Uuid::uuid4()->toString(),
                    'GTMS'             => 1,
                    'NOT_GTMS'         => 1,
                    'COMMENTS'         => 1,
                    'CI_STATUS'        => 'PENDING',
                    'IS_MERGED'        => true,
                    'MESSAGE_IDS'      => ['1', '2'],
                    'PUT_TO_REVIEW_AT' => (string) $now->getTimestamp(),
                    'MERGED_AT'        => (string) $mergedAt->getTimestamp()
                ]
            )
        );
    }

    private function createPRWithoutPutToReviewDate(): void
    {
        /** @var Connection $connection */
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $sql = <<<SQL
INSERT INTO `pr` (`IDENTIFIER`, `GTMS`, `NOT_GTMS`, `COMMENTS`, `CI_STATUS`, `IS_MERGED`, `MESSAGE_IDS`, `rows_before_migration_Version20190609163730`, `PUT_TO_REVIEW_AT`, `MERGED_AT`)
VALUES
	('pr_identifier', 3, 0, 1, 'PENDING', 1, '{}', 1, NULL, '251512');
SQL;
        $connection->executeUpdate($sql);
    }

    private function createPRWithoutMergedAtDate(): void
    {
        /** @var Connection $connection */
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $sql = <<<SQL
INSERT INTO `pr` (`IDENTIFIER`, `GTMS`, `NOT_GTMS`, `COMMENTS`, `CI_STATUS`, `IS_MERGED`, `MESSAGE_IDS`, `rows_before_migration_Version20190609163730`, `PUT_TO_REVIEW_AT`, `MERGED_AT`)
VALUES
	('pr_identifier', 3, 0, 1, 'PENDING', 1, '{}', 1, '251512', NULL);
SQL;
        $connection->executeUpdate($sql);
    }
}
