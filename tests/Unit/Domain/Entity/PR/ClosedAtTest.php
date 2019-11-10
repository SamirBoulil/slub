<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity\PR;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\ClosedAt;

class ClosedAtTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_closed_at_date()
    {
        $ClosedAt = ClosedAt::create();
        $this->assertNotEmpty($ClosedAt);
    }

    /**
     * @test
     */
    public function it_creates_a_closed_at_value_with_no_date()
    {
        $emptyClosedAt = ClosedAt::none();
        $this->assertEmpty($emptyClosedAt->toTimestamp());

        $emptyClosedAt = ClosedAt::fromTimestampIfAny(null);
        $this->assertEmpty($emptyClosedAt->toTimestamp());
    }

    /**
     * @test
     */
    public function it_creates_a_closed_at_date_from_timestamp()
    {
        $aTimestamp = $this->aTimestamp();

        $ClosedAt = ClosedAt::fromTimestampIfAny($aTimestamp);

        $this->assertEquals($aTimestamp, $ClosedAt->toTimestamp());
    }

    private function aTimestamp(): string
    {
        $aDate = new \DateTime('now', new \DateTimeZone('UTC'));

        return (string) $aDate->getTimestamp();
    }
}
