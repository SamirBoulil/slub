<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\GraphQL;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatusInterface;

/**
 * Computes the CI status of a PR with a single GraphQL query: the status check
 * rollup of the head commit aggregates both check runs and commit statuses, so
 * the three REST calls of the legacy GetCIStatus collapse into one API point.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class GetCIStatusViaGraphQL implements GetCIStatusInterface
{
    private const QUERY = <<<'GRAPHQL'
        query CIStatus($owner: String!, $name: String!, $number: Int!) {
          repository(owner: $owner, name: $name) {
            pullRequest(number: $number) {
              mergeable
              mergeStateStatus
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
    ) {
    }

    /**
     * $commitRef is unused: the status check rollup is always computed on the PR's
     * current head, which is the commit the CI status of the PR should reflect.
     */
    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CIStatus
    {
        return $this->ciStatusFromPullRequestNode->fromPullRequestNode(
            $this->fetchPullRequestNode->fetch($PRIdentifier, self::QUERY)
        );
    }
}
