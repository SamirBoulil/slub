<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        GetCheckSuiteStatus $getCheckSuiteStatus,
        GetCheckRunStatus $getCheckRunStatus,
        GetStatusChecksStatus $getStatusChecksStatus,
        LoggerInterface $logger
    ) {
        $this->getCheckSuiteStatus = $getCheckSuiteStatus;
        $this->getCheckRunStatus = $getCheckRunStatus;
        $this->getStatusChecksStatus = $getStatusChecksStatus;
        $this->logger = $logger;
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): string
    {
//        $checkSuiteStatus = $this->getCheckSuiteStatus->fetch($PRIdentifier, $commitRef);
//        $this->logger->critical('Check suite CI: ' . $checkSuiteStatus);
//        if ('PENDING' !== $checkSuiteStatus) {
//            return $checkSuiteStatus;
//        }
        $checkRunStatus = $this->getCheckRunStatus->fetch($PRIdentifier, $commitRef);
        $this->logger->critical('Check run CI: ' . $checkRunStatus);
        $statusCheckStatus = $this->getStatusChecksStatus->fetch($PRIdentifier, $commitRef);
        $this->logger->critical('status check: ' . $statusCheckStatus);

        $deductCIStatus = $this->deductCIStatus($checkRunStatus, $statusCheckStatus);
        $this->logger->critical('status = ' . $deductCIStatus);

        return $deductCIStatus;
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
