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
}
