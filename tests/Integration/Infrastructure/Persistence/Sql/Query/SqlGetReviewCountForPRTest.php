<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Query;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Query\GetReviewCountForPR;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SqlGetReviewCountForPRTest extends KernelTestCase
{
    private const PR_IDENTIFIER = 'akeneo/pim-community-dev/1111';

    /** @var GetReviewCountForPR */
    private $getReviewCountForPR;

    public function setUp(): void
    {
        parent::setUp();
        $this->getReviewCountForPR = $this->get('slub.infrastructure.persistence.get_review_count_for_pr');
        $this->resetDB();
    }

    /**
     * @test
     */
    public function it_returns_the_number_of_reviews_a_pr_has()
    {
        $this->createPRWithReviews(1, 1, 1);
        $reviewCount = $this->getReviewCountForPR->fetch(PRIdentifier::fromString(self::PR_IDENTIFIER));
        $this->assertEquals(2, $reviewCount);
    }

    /**
     * @test
     */
    public function it_throws_if_the_PR_does_not_exists()
    {
        $this->expectException(PRNotFoundException::class);
        $this->getReviewCountForPR->fetch(PRIdentifier::fromString('unknown_identifier'));
    }

    private function createPRWithReviews(int $gtms, int $notGtms, int $comments): void
    {
        /** @var PRRepositoryInterface $PRRepository */
        $PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $PR = PR::fromNormalized([
            'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
            'GTMS' => $gtms,
            'NOT_GTMS' => $notGtms,
            'COMMENTS' => $comments,
            'CI_STATUS' => 'PENDING',
            'IS_MERGED' => false,
            'MESSAGE_IDS' => ['1'],
        ]);
        $PRRepository->save($PR);
    }

    private function resetDB(): void
    {
        $sqlPRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $sqlPRRepository->reset();
    }
}
