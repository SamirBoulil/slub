<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
interface GetAverageTimeToMergeInterface
{
    public function fetch(): ?int;
}
