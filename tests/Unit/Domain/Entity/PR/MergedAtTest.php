<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MergedAt;

class MergedAtTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_merged_with_a_value()
    {
        $mergedAt = MergedAt::create();
        $this->assertNotEmpty($mergedAt);
    }

    /**
     * @test
     */
    public function it_creates_a_merged_at_value_with_no_date()
    {
        $emptyMergedAt = MergedAt::none();
        $this->assertEmpty($emptyMergedAt->stringValue());
    }

    /**
     * @test
     */
    public function it_creates_a_put_to_review_date_from_string()
    {
        $aDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $mergedAt = MergedAt::fromString($aDate);

        $this->assertEquals($aDate, $mergedAt->stringValue());
    }
}
