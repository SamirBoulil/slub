<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetCheckRunStatus;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetMergeableState;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\GetStatusChecksStatus;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetCIStatus
{
    /** @var string[] */
    private array $supportedCIChecks;

    public function __construct(
        private GetMergeableState $getMergeableState,
        private GetCheckRunStatus $getCheckRunStatus,
        private GetStatusChecksStatus $getStatusChecksStatus,
        private LoggerInterface $logger,
        string $supportedCiChecks
    ) {
        $this->supportedCIChecks = explode(',', $supportedCiChecks);
    }

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CIStatus
    {
        $isMergeable = $this->getMergeableState->fetch($PRIdentifier);
        // $this->logger->critical('Is mergeable: ' . $isMergeable ? 'true' : 'false');
        if ($isMergeable) {
            return CIStatus::green();
        }

        $checkRunStatus = $this->getCheckRunStatus->fetch($PRIdentifier, $commitRef);
        $statusCheckStatus = $this->getStatusChecksStatus->fetch($PRIdentifier, $commitRef);
        $allCheckStatuses = array_merge($checkRunStatus, $statusCheckStatus);
        $deductCIStatus = $this->deductCIStatus($allCheckStatuses);

        // $this->logger->critical('Result status = ' . $deductCIStatus->status);

        return $deductCIStatus;
    }

    private function deductCIStatus(array $allCheckStatuses): CIStatus
    {
        $failedCheckStatus = $this->failedCheckStatus($allCheckStatuses);
        if (null !== $failedCheckStatus) {
            return $failedCheckStatus;
        }

        if ($this->areAllSuccessful($allCheckStatuses)) {
            return CIStatus::green('');
        }

        $supportedCheckStatuses = $this->supportedCheckStatus($allCheckStatuses);
        if (empty($supportedCheckStatuses)) {
            return CIStatus::pending();
        }

        if ($this->areAllSuccessful($supportedCheckStatuses)) {
            return CIStatus::green();
        }

        return CIStatus::pending();
    }

    /**
     * @param array<CIStatus> $allCheckStatuses
     */
    private function areAllSuccessful(array $allCheckStatuses): bool
    {
        $successfulCICheckStatuses = array_filter(
            $allCheckStatuses,
            static fn (CIStatus $checkStatus) => $checkStatus->isGreen()
        );

        return \count($successfulCICheckStatuses) === \count($allCheckStatuses);
    }

    private function failedCheckStatus(array $allCheckStatuses): ?CIStatus
    {
        return array_reduce(
            $allCheckStatuses,
            static function ($current, CIStatus $checkStatus) {
                if (null !== $current) {
                    return $current;
                }

                return $checkStatus->isRed() ? $checkStatus : $current;
            },
            null
        );
    }

    private function supportedCheckStatus(array $allCheckStatuses): array
    {
        return array_filter(
            $allCheckStatuses,
            fn(CIStatus $checkStatus) => \in_array($checkStatus->name, $this->supportedCIChecks, true)
        );
    }
}
