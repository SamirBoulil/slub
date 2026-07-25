<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\GraphQL;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetCIStatusInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

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
        private GithubAPIClientInterface $githubAPIClient,
        private CIStatusFromPullRequestNode $ciStatusFromPullRequestNode,
        private string $domainName,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * $commitRef is unused: the status check rollup is always computed on the PR's
     * current head, which is the commit the CI status of the PR should reflect.
     */
    public function fetch(PRIdentifier $PRIdentifier, string $commitRef): CIStatus
    {
        return $this->ciStatusFromPullRequestNode->fromPullRequestNode($this->pullRequestNode($PRIdentifier));
    }

    private function pullRequestNode(PRIdentifier $PRIdentifier): array
    {
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        [$owner, $name] = explode('/', $repositoryIdentifier);
        $response = $this->githubAPIClient->post(
            sprintf('%s/graphql', $this->domainName),
            [
                'json' => [
                    'query' => self::QUERY,
                    'variables' => [
                        'owner' => $owner,
                        'name' => $name,
                        'number' => (int) GithubAPIHelper::PRNumber($PRIdentifier),
                    ],
                ],
            ],
            $repositoryIdentifier
        );

        $content = json_decode($response->getBody()->getContents(), true);
        if (200 !== $response->getStatusCode()
            || null === $content
            || isset($content['errors'])
            || !isset($content['data']['repository']['pullRequest'])
        ) {
            $this->logger->error(
                sprintf(
                    'Unexpected GraphQL response when fetching the CI status for PR "%s": status %d, body "%s"',
                    $PRIdentifier->stringValue(),
                    $response->getStatusCode(),
                    (string) json_encode($content)
                )
            );

            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the CI status for PR "%s" (status %d)',
                    $PRIdentifier->stringValue(),
                    $response->getStatusCode()
                )
            );
        }

        return $content['data']['repository']['pullRequest'];
    }
}
