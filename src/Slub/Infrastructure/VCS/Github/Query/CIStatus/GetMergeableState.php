<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\CIStatus;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\GetPRDetails;

class GetMergeableState
{
    public function __construct(private GetPRDetails $getPRDetails)
    {
    }

    public function fetch(PRIdentifier $PRIdentifier): bool
    {
        $mergeableInfo = $this->getPRDetails->fetch($PRIdentifier);

        return true === $mergeableInfo['mergeable']
            && 'clean' === $mergeableInfo['mergeable_state'];
    }
}
