<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRInfoInterfaceDummy implements GetPRInfoInterface
{
    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        $result = new PRInfo();
        $result->GTMCount = 0;
        $result->notGTMCount = 0;
        $result->comments = 0;
        $result->CIStatus = new CheckStatus('PENDING');
        $result->isMerged = false;

        return $result;
    }
}
