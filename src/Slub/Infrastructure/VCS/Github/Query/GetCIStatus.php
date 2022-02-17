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

    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CheckStatus
    {
        $isMergeable = $this->getMergeableState->fetch($PRIdentifier);
        $this->logger->critical('Is mergeable: ' . $isMergeable ? 'true' : 'false');
        if ($isMergeable) {
            return CheckStatus::green();
        }

        $checkRunStatus = $this->getCheckRunStatus->fetch($PRIdentifier, $commitRef);
        $statusCheckStatus = $this->getStatusChecksStatus->fetch($PRIdentifier, $commitRef);
        $allCheckStatuses = array_merge($checkRunStatus, $statusCheckStatus);
        $deductCIStatus = $this->deductCIStatus($allCheckStatuses);

        $this->logger->critical('Result status = ' . $deductCIStatus->status);

        return $deductCIStatus;
    }

    private function deductCIStatus(array $allCheckStatuses): CheckStatus
    {
        $failedCheckStatus = $this->failedCheckStatus($allCheckStatuses);
        if (null !== $failedCheckStatus) {
            return $failedCheckStatus;
        }

        if ($this->areAllSuccessful($allCheckStatuses)) {
            return CheckStatus::green('');
        }

        $supportedCheckStatuses = $this->supportedCheckStatus($allCheckStatuses);
        if (empty($supportedCheckStatuses)) {
            return CheckStatus::pending();
        }

        if ($this->areAllSuccessful($supportedCheckStatuses)) {
            return CheckStatus::green();
        }

        return CheckStatus::pending();
    }

    /**
     * @param array<CheckStatus> $allCheckStatuses
     */
    private function areAllSuccessful(array $allCheckStatuses): bool
    {
        $successfulCICheckStatuses = array_filter(
            $allCheckStatuses,
            static fn (CheckStatus $checkStatus) => $checkStatus->isGreen()
        );

        return \count($successfulCICheckStatuses) === \count($allCheckStatuses);
    }

    private function failedCheckStatus(array $allCheckStatuses): ?CheckStatus
    {
        return array_reduce(
            $allCheckStatuses,
            static function ($current, CheckStatus $checkStatus) {
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
            fn(CheckStatus $checkStatus) => \in_array($checkStatus->name, $this->supportedCIChecks, true)
        );
    }
}
