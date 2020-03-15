<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Query\ClockInterface;

class InMemoryClock implements ClockInterface
{
    private $areWeOnWeekEnd = false;

    public function areWeOnWeekEnd(): bool
    {
        return $this->areWeOnWeekEnd;
    }

    public function YesWeAReOneWeekEnd(): void
    {
        $this->areWeOnWeekEnd = true;
    }
}
