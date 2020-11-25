<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClient;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRDetails
{
    /** @var GithubAPIClient */
    private $githubAPIClient;

    public function __construct(GithubAPIClient $githubAPIClient)
    {
        $this->githubAPIClient = $githubAPIClient;
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $url = $this->getUrl($PRIdentifier);

        return $this->fetchPRdetails($PRIdentifier, $url);
    }

    private function getUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);

        return sprintf('https://api.github.com/repos/%s/%s/pulls/%s', ...$matches);
    }

    private function fetchPRdetails(PRIdentifier $PRIdentifier, string $url): array
    {
        $repositoryIdentifier = $this->repositoryIdentifier($PRIdentifier);
        $response = $this->githubAPIClient->get($url, [], $repositoryIdentifier);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(sprintf('There was a problem when fetching the reviews for PR "%s" at %s', $PRIdentifier->stringValue(), $url));
        }

        return $content;
    }

    private function repositoryIdentifier(PRIdentifier $PRIdentifier): string
    {
        return sprintf('%s/%s', ...GithubAPIHelper::breakoutPRIdentifier($PRIdentifier));
    }
}
