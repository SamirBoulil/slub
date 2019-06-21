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
    public function it_creates_a_merged_at_date()
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
        $this->assertEmpty($emptyMergedAt->toTimestamp());

        $emptyMergedAt = MergedAt::fromTimestampIfAny(null);
        $this->assertEmpty($emptyMergedAt->toTimestamp());
    }

    /**
     * @test
     */
    public function it_creates_a_merged_at_date_from_timestamp()
    {
        $aTimestamp = $this->aTimestamp();

        $mergedAt = MergedAt::fromTimestampIfAny($aTimestamp);

        $this->assertEquals($aTimestamp, $mergedAt->toTimestamp());
    }

    private function aTimestamp(): string
    {
        $aDate = new \DateTime('now', new \DateTimeZone('UTC'));

        return (string) $aDate->getTimestamp();
    }
}
