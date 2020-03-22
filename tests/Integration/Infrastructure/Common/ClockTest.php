<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Common;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Common\Clock;

class ClockTest extends TestCase
{
    /**
     * @test
     * @dataProvider dates
     */
    public function it_tells_if_we_are_on_the_week_end_or_not(\DateTime $date, bool $isOnWeekend)
    {
        /** @var Clock&MockObject $clockMock */
        $clockMock = $this->getMockBuilder(Clock::class)->setMethods(['getDate'])->getMock();
        $clockMock->expects($this->at(0))->method('getDate')->willReturn($date);

        $this->assertEquals($isOnWeekend, $clockMock->areWeOnWeekEnd());
    }

    public function dates(): array
    {
        return
            [
                'Friday' => [new \DateTime('2020-03-22', new \DateTimeZone('UTC')), true],
                'Monday' => [new \DateTime('2020-03-23', new \DateTimeZone('UTC')), false],
            ];
    }
}
