<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\GraphQL;

use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;

/**
 * Deducts the CI status of a PR from a GraphQL "pullRequest" node carrying the
 * mergeability fields and the status check rollup of its head commit.
 *
 * The deduction rules are a copy of the ones in GetCIStatus, fed from the rollup
 * contexts instead of the REST check-runs/statuses endpoints. The duplication is
 * deliberate: the legacy path stays untouched while FF_GITHUB_GRAPHQL is being
 * rolled out, and dies with it once the flag graduates.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusFromPullRequestNode
{
    private const CHECK_RUN_TYPENAME = 'CheckRun';
    private const SUCCESS = 'SUCCESS';
    private const FAILURE = 'FAILURE';

    /** @var string[] */
    private array $supportedCIChecks;

    public function __construct(string $supportedCiChecks)
    {
        $this->supportedCIChecks = explode(',', $supportedCiChecks);
    }

    public function fromPullRequestNode(array $pullRequestNode): CIStatus
    {
        if ($this->isMergeableAndClean($pullRequestNode)) {
            return CIStatus::green();
        }

        return $this->deductCIStatus($this->checkStatuses($pullRequestNode));
    }

    private function isMergeableAndClean(array $pullRequestNode): bool
    {
        return 'MERGEABLE' === ($pullRequestNode['mergeable'] ?? null)
            && 'CLEAN' === ($pullRequestNode['mergeStateStatus'] ?? null);
    }

    /**
     * @return array<CIStatus>
     */
    private function checkStatuses(array $pullRequestNode): array
    {
        $statusCheckRollup = $pullRequestNode['commits']['nodes'][0]['commit']['statusCheckRollup'] ?? null;
        if (null === $statusCheckRollup) {
            return [];
        }

        return array_map(
            fn (array $contextNode) => $this->checkStatus($contextNode),
            $statusCheckRollup['contexts']['nodes'] ?? []
        );
    }

    private function checkStatus(array $contextNode): CIStatus
    {
        if (self::CHECK_RUN_TYPENAME === ($contextNode['__typename'] ?? null)) {
            return match ($contextNode['conclusion'] ?? null) {
                self::SUCCESS => CIStatus::green($contextNode['name']),
                self::FAILURE => CIStatus::red($contextNode['name'], $contextNode['detailsUrl'] ?? ''),
                default => CIStatus::pending($contextNode['name']),
            };
        }

        return match ($contextNode['state'] ?? null) {
            self::SUCCESS => CIStatus::green($contextNode['context']),
            self::FAILURE => CIStatus::red($contextNode['context'], $contextNode['targetUrl'] ?? ''),
            default => CIStatus::pending($contextNode['context']),
        };
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
            fn (CIStatus $checkStatus) => \in_array($checkStatus->name, $this->supportedCIChecks, true)
        );
    }
}
