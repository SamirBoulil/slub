<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetMergeableState;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetCIStatus
{
    public function __construct(
        private GetMergeableState $getMergeableState,
        private GetCheckRunStatus $getCheckRunStatus,
        private GetStatusChecksStatus $getStatusChecksStatus,
        private LoggerInterface $logger
    ) {
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CheckStatus
    {
        $isMergeable = $this->getMergeableState->fetch($PRIdentifier);
        $this->logger->critical('Is mergeable: ' . $isMergeable ? 'true' : 'false');
        if ($isMergeable) {
            return new CheckStatus('GREEN');
        }

        $checkRunStatus = $this->getCheckRunStatus->fetch($PRIdentifier, $commitRef);
        $statusCheckStatus = $this->getStatusChecksStatus->fetch($PRIdentifier, $commitRef);
        $deductCIStatus = $this->deductCIStatus($checkRunStatus, $statusCheckStatus);

        $this->logger->critical('Check run CI: '.$checkRunStatus->status);
        $this->logger->critical('status check: '.$statusCheckStatus->status);
        $this->logger->critical('Result status = '.$deductCIStatus->status);

        return $deductCIStatus;
    }

    private function deductCIStatus(CheckStatus $checkStatus, CheckStatus $statusCheckStatus): CheckStatus
    {
        if ('RED' === $checkStatus->status) {
            return $checkStatus;
        }
        if ('RED' === $statusCheckStatus->status) {
            return $statusCheckStatus;
        }
        if ('GREEN' === $checkStatus->status) {
            return $checkStatus;
        }
        if ('GREEN' === $statusCheckStatus->status) {
            return $statusCheckStatus;
        }

        return new CheckStatus('PENDING');
    }
}
