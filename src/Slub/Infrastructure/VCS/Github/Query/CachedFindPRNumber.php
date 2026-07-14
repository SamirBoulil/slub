<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;

/**
 * Resolves the PR number of a commit from the pr_commits table (fed for free by the
 * "pull_request" webhooks, see RecordPRCommitEventHandler) and only falls back to the
 * Github API for commits that are not known yet, caching the API result.
 *
 * Commits without a PR are never cached: a PR can be opened later for that commit,
 * and only its head sha would be repaired by the "pull_request" webhooks.
 *
 * The cache is best effort only: any cache failure falls back to calling the Github
 * API exactly as if the cache did not exist.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class CachedFindPRNumber implements FindPRNumberInterface
{
    public function __construct(
        private SqlPRCommitsRepository $prCommitsRepository,
        private FindPRNumberInterface $findPRNumber,
        private LoggerInterface $logger
    ) {
    }

    public function fetch(string $repository, string $commitRef): ?string
    {
        $cachedPRNumber = $this->findCachedPRNumber($repository, $commitRef);
        if (null !== $cachedPRNumber) {
            return $cachedPRNumber;
        }

        $PRNumber = $this->findPRNumber->fetch($repository, $commitRef);
        if (null !== $PRNumber) {
            $this->cachePRNumber($repository, $commitRef, $PRNumber);
        }

        return $PRNumber;
    }

    private function findCachedPRNumber(string $repository, string $commitRef): ?string
    {
        try {
            $cachedPRCommit = $this->prCommitsRepository->find($repository, $commitRef);

            return null !== $cachedPRCommit ? $cachedPRCommit['PR_NUMBER'] : null;
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf('Unable to read the PR number cache for commit "%s" on "%s": %s', $commitRef, $repository, $e->getMessage())
            );

            return null;
        }
    }

    private function cachePRNumber(string $repository, string $commitRef, string $PRNumber): void
    {
        try {
            $this->prCommitsRepository->save($repository, $commitRef, $PRNumber);
        } catch (\Exception|\Error $e) {
            $this->logger->warning(
                sprintf('Unable to cache the PR number of commit "%s" on "%s": %s', $commitRef, $repository, $e->getMessage())
            );
        }
    }
}
