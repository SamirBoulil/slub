<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\GraphQL;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * Fetches everything a PRInfo needs (details, reviews and CI status) with a single
 * GraphQL query, where the legacy GetPRInfo needs five REST calls.
 *
 * The mapping mirrors the legacy implementation, including its quirks: isMerged and
 * isClosed are both "the PR is not open anymore", and the not-GTM count looks for a
 * "REFUSED" review state that Github never sends (it stays 0, exactly like the REST
 * path, which filters reviews on the same string).
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRInfoViaGraphQL implements GetPRInfoInterface
{
    private const APPROVED = 'APPROVED';
    private const REFUSED = 'REFUSED';
    private const COMMENTED = 'COMMENTED';
    private const OPEN = 'OPEN';

    private const QUERY = <<<'GRAPHQL'
        query PRInfo($owner: String!, $name: String!, $number: Int!) {
          repository(owner: $owner, name: $name) {
            pullRequest(number: $number) {
              title
              body
              state
              author { login avatarUrl }
              additions
              deletions
              headRefOid
              mergeable
              mergeStateStatus
              reviews(first: 100) { nodes { state } }
              commits(last: 1) {
                nodes {
                  commit {
                    statusCheckRollup {
                      state
                      contexts(first: 100) {
                        nodes {
                          __typename
                          ... on CheckRun { name conclusion detailsUrl }
                          ... on StatusContext { context state targetUrl }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
        GRAPHQL;

    public function __construct(
        private FetchPullRequestNode $fetchPullRequestNode,
        private CIStatusFromPullRequestNode $ciStatusFromPullRequestNode,
        private SqlPRCommitsRepository $prCommitsRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function fetch(PRIdentifier $PRIdentifier): PRInfo
    {
        $pullRequestNode = $this->fetchPullRequestNode->fetch($PRIdentifier, self::QUERY);
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        $this->recordPRHeadCommit($PRIdentifier, $repositoryIdentifier, $pullRequestNode);

        $result = new PRInfo();
        $result->PRIdentifier = $PRIdentifier->stringValue();
        $result->repositoryIdentifier = $repositoryIdentifier;
        $result->authorIdentifier = $pullRequestNode['author']['login'] ?? '';
        $result->authorImageUrl = $pullRequestNode['author']['avatarUrl'] ?? '';
        $result->title = $pullRequestNode['title'];
        $result->description = $pullRequestNode['body'] ?? '';
        $result->GTMCount = $this->countReviews($pullRequestNode, self::APPROVED);
        $result->notGTMCount = $this->countReviews($pullRequestNode, self::REFUSED);
        $result->comments = $this->countReviews($pullRequestNode, self::COMMENTED);
        $result->CIStatus = $this->ciStatusFromPullRequestNode->fromPullRequestNode($pullRequestNode);
        $result->isMerged = $this->isClosed($pullRequestNode);
        $result->isClosed = $this->isClosed($pullRequestNode);
        $result->additions = $pullRequestNode['additions'];
        $result->deletions = $pullRequestNode['deletions'];

        return $result;
    }

    private function isClosed(array $pullRequestNode): bool
    {
        return self::OPEN !== $pullRequestNode['state'];
    }

    private function countReviews(array $pullRequestNode, string $state): int
    {
        return \count(
            array_filter(
                $pullRequestNode['reviews']['nodes'] ?? [],
                static fn (array $review) => $state === ($review['state'] ?? null)
            )
        );
    }

    /**
     * Warms the pr_commits table so that "status" events for this PR can be resolved
     * without calling the Github API (see CachedFindPRNumber). Best effort only:
     * fetching the PR info should never fail because of it.
     */
    private function recordPRHeadCommit(PRIdentifier $PRIdentifier, string $repositoryIdentifier, array $pullRequestNode): void
    {
        try {
            $this->prCommitsRepository->saveHeadCommit(
                $repositoryIdentifier,
                $pullRequestNode['headRefOid'],
                GithubAPIHelper::PRNumber($PRIdentifier)
            );
        } catch (\Exception|\Error $e) {
            $this->logger->error(
                sprintf('Unable to record the head commit of PR "%s": %s', $PRIdentifier->stringValue(), $e->getMessage())
            );
        }
    }
}
