<?php
declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;

interface GetReviewCountForPR
{
    /** @return int */
    public function fetch(PRIdentifier $PRIdentifier): int;
}
