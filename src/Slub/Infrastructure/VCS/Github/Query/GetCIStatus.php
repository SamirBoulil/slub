<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CheckStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetCIStatus
{
    /** @var GetCheckRunStatus */
    private $getCheckRunStatus;

    /** @var GetStatusChecksStatus */
    private $getStatusChecksStatus;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        GetCheckRunStatus $getCheckRunStatus,
        GetStatusChecksStatus $getStatusChecksStatus,
        LoggerInterface $logger
    ) {
        $this->getCheckRunStatus = $getCheckRunStatus;
        $this->getStatusChecksStatus = $getStatusChecksStatus;
        $this->logger = $logger;
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CheckStatus
    {
        $checkRunStatus = $this->getCheckRunStatus->fetch($PRIdentifier, $commitRef);
        $this->logger->critical('Check run CI: ' . $checkRunStatus->status);

        $statusCheckStatus = $this->getStatusChecksStatus->fetch($PRIdentifier, $commitRef);
        $this->logger->critical('status check: ' . $statusCheckStatus->status);

        $deductCIStatus = $this->deductCIStatus($checkRunStatus, $statusCheckStatus);
        $this->logger->critical('status = ' . $deductCIStatus->status);

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
