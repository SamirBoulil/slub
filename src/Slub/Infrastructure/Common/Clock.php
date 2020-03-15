<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Common;

use Slub\Domain\Query\ClockInterface;

class Clock implements ClockInterface
{
    private const SATURDAY = 5;
    private const SUNDAY = 6;

    public function areWeOnWeekEnd(): bool
    {
        $dayOfTheWeek = (int) date('w');

        return self::SATURDAY === $dayOfTheWeek || self::SUNDAY === $dayOfTheWeek;
    }
}
