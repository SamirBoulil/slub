<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

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
    public function it_does_not_take_into_account_pr_not_having_dates()
    {
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
}
