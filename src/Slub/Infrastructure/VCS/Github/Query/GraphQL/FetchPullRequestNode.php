<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query\GraphQL;

use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;
use Slub\Infrastructure\VCS\Github\Query\GithubAPIHelper;

/**
 * Runs a GraphQL query against the Github API and returns the "pullRequest" node
 * of the response. GraphQL failures come back as a 200 with an "errors" array (and
 * a possibly null "data"), so the response body is validated, not just the status.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class FetchPullRequestNode
{
    public function __construct(
        private GithubAPIClientInterface $githubAPIClient,
        private string $domainName,
        private LoggerInterface $logger,
    ) {
    }

    public function fetch(PRIdentifier $PRIdentifier, string $query): array
    {
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        [$owner, $name] = explode('/', $repositoryIdentifier);
        $response = $this->githubAPIClient->post(
            sprintf('%s/graphql', $this->domainName),
            [
                'json' => [
                    'query' => $query,
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
                    'Unexpected GraphQL response for PR "%s": status %d, body "%s"',
                    $PRIdentifier->stringValue(),
                    $response->getStatusCode(),
                    (string) json_encode($content)
                )
            );

            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the information for PR "%s" (status %d)',
                    $PRIdentifier->stringValue(),
                    $response->getStatusCode()
                )
            );
        }

        return $content['data']['repository']['pullRequest'];
    }
}
