<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckSuiteStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetCIStatus
{
    /** @var GetCheckSuiteStatus */
    private $getCheckSuiteStatus;

    /** @var GetCheckRunStatus */
    private $getCheckRunStatus;

    /** @var GetStatusChecksStatus */
    private $getStatusChecksStatus;

    public function __construct(GetCheckSuiteStatus $getCheckSuiteStatus, GetCheckRunStatus $getCheckRunStatus, GetStatusChecksStatus $getStatusChecksStatus)
    {
        $this->getCheckSuiteStatus = $getCheckSuiteStatus;
        $this->getCheckRunStatus = $getCheckRunStatus;
        $this->getStatusChecksStatus = $getStatusChecksStatus;
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): string
    {
        $checkSuiteStatus = $this->getCheckSuiteStatus->fetch($PRIdentifier, $commitRef);
        if ('PENDING' !== $checkSuiteStatus) {
            return $checkSuiteStatus;
        }
        $checkRunStatus = $this->getCheckRunStatus->fetch($PRIdentifier, $commitRef);
        $statusCheckStatus = $this->getStatusChecksStatus->fetch($PRIdentifier, $commitRef);

        return $this->deductCIStatus($checkRunStatus, $statusCheckStatus);
    }

    private function deductCIStatus(string $checkRunStatus, string $statusCheckStatus): string
    {
        if ('RED' === $checkRunStatus || 'RED' === $statusCheckStatus) {
            return 'RED';
        }

        if ('GREEN' === $checkRunStatus || 'GREEN' === $statusCheckStatus) {
            return 'GREEN';
        }

        return 'PENDING';
    }
}
