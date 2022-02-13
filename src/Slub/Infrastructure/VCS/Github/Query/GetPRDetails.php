<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRDetails
{
    public function __construct(private GithubAPIClient $githubAPIClient)
    {
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $url = GithubAPIHelper::PRAPIUrl($PRIdentifier);

        return $this->fetchPRdetails($PRIdentifier, $url);
    }

    private function fetchPRdetails(PRIdentifier $PRIdentifier, string $url): array
    {
        $repositoryIdentifier = GithubAPIHelper::repositoryIdentifierFrom($PRIdentifier);
        $response = $this->githubAPIClient->get($url, [], $repositoryIdentifier);

        $content = json_decode($response->getBody()->getContents(), true);
        if (200 !== $response->getStatusCode() || null === $content) {
            throw new \RuntimeException(sprintf('There was a problem when fetching the reviews for PR "%s" at %s', $PRIdentifier->stringValue(), $url));
        }

        return $content;
    }
}
