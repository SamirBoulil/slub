<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
interface GetAverageTimeToMergeInterface
{
    public function fetch(): ?int;
}
