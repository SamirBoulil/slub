<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

/**
 */
interface ClockInterface
{
    public function areWeOnWeekEnd(): bool;
}
