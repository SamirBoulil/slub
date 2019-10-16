<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use GuzzleHttp\Client;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FindPRNumber implements FindPRNumberInterface
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

    public function fetch(string $repository, string $commitRef): ?string
    {
        $searchResult = $this->searchResult($repository, $commitRef);

        return $this->prNumber($searchResult);
    }

    private function searchResult(string $repository, string $commitRef): array
    {
        $url = $this->searchUrl($repository, $commitRef);
        $response = $this->httpClient->get($url, ['headers' => $this->getHeaders()]);

        $content = json_decode($response->getBody()->getContents(), true);
        if (null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the check runs for repository "%s" (%s) and commit ref %s',
                    $repository,
                    $commitRef,
                    $url
                )
            );
        }

        return $content;
    }

    private function searchUrl(string $repository, string $commitRef): string
    {
        return sprintf('https://api.github.com/search/issues?q=%s+%s', $repository, $commitRef);
    }

    private function getHeaders(): array
    {
        return GithubAPIHelper::authorizationHeader($this->authToken);
    }

    private function prNumber(array $searchResult): ?string
    {
        if (empty($searchResult['items'])) {
            return null;
        }

        preg_match('/\d+$/', $searchResult['items'][0]['pull_request']['url'], $matches);

        return isset($matches[0]) ? $matches[0] : null;
    }
}
