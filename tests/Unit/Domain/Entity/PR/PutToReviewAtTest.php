<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\PutToReviewAt;

class PutToReviewAtTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_put_to_review_at_date()
    {
        $putToReviewAt = PutToReviewAt::create();
        $this->assertNotEmpty($putToReviewAt);
    }

    /**
     * @test
     */
    public function it_creates_a_put_to_review_date_from_timestamp()
    {
        $aTimestamp = (string) (new \DateTime())->getTimestamp();

        $putToReviewAt = PutToReviewAt::fromTimestamp($aTimestamp);

        $toTimestamp = $putToReviewAt->toTimestamp();
        $this->assertEquals($aTimestamp, $toTimestamp);
    }

    /**
     * @test
     * @dataProvider date
     */
    public function it_tells_the_number_of_days_between_now_and_the_time_the_PR_has_been_put_in_review(
        string $timestamp,
        int $expectedNumberOfDays
    ) {
        $putToReviewAt = PutToReviewAt::fromTimestamp($timestamp);

        $actualNumberOfDaysInReview = $putToReviewAt->numberOfDaysInReview();

        $this->assertEquals($expectedNumberOfDays, $actualNumberOfDaysInReview);
    }

    public function date()
    {
        $nowTimestamp = (string) (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
        $yesterday = (string) (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day')->getTimestamp();

        return [
            'Today' => [$nowTimestamp, 0],
            '1 day' => [$yesterday, 1],
        ];
    }
}
