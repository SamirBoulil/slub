<?php

declare(strict_types=1);

namespace Slub\Domain\Query;

use Slub\Domain\Entity\PR\PRIdentifier;

interface PRIsInReview
{
    public function fetch(PRIdentifier $PRIdentifier): bool;
}
