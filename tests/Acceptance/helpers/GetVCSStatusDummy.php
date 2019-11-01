<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetVCSStatus;
use Slub\Domain\Query\VCSStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetVCSStatusDummy implements GetVCSStatus
{
    public function fetch(PRIdentifier $PRIdentifier): VCSStatus
    {
        $result = new VCSStatus();
        $result->GTMCount = 0;
        $result->notGTMCount = 0;
        $result->comments = 0;
        $result->checkStatus = 'PENDING';
        $result->isMerged = false;

        return $result;
    }
}
