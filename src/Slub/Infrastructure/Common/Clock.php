<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Common;

use Slub\Domain\Query\ClockInterface;

class Clock implements ClockInterface
{
    private const SATURDAY = 6;
    private const SUNDAY = 7;

    public function areWeOnWeekEnd(): bool
    {
        $now = $this->getDate();
        $dayOfTheWeek = (int) $now->format('N');

        return self::SATURDAY === $dayOfTheWeek || self::SUNDAY === $dayOfTheWeek;
    }

    public function getDate(): \DateTime
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
