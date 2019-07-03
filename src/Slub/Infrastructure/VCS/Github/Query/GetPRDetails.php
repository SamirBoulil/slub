<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;
use Slub\Domain\Entity\PR\PRIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetPRDetails
{
    /** @var Client */
    private $httpClient;

    /** @var string */
    private $authToken;

    public function __construct(Client $httpClient, string $authToken)
    {
        $this->httpClient = $httpClient;
        $this->authToken = $authToken;
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $url = $this->getUrl($PRIdentifier);

        return $this->fetchPRdetails($PRIdentifier, $url);
    }

    private function getUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);
        $url = sprintf('https://api.github.com/repos/%s/%s/pulls/%s', ...$matches);

        return $url;
    }

    private function fetchPRdetails(PRIdentifier $PRIdentifier, string $url): array
    {
        $response = $this->httpClient->get($url, ['headers' => GithubAPIHelper::authorizationHeader($this->authToken)]);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the reviews for PR "%s" at %s',
                    $PRIdentifier->stringValue(),
                    $url
                )
            );
        }

        return $content;
    }
}
