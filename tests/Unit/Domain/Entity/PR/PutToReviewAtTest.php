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
    public function it_creates_a_put_to_review()
    {
        $putToReviewAt = PutToReviewAt::create();
        $this->assertNotEmpty($putToReviewAt);
    }

    /**
     * @test
     */
    public function it_creates_a_put_to_review_date_from_string()
    {
        $aDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $putToReviewAt = PutToReviewAt::fromString($aDate);

        $this->assertEquals($aDate, $putToReviewAt->stringValue());
    }
}
