<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class FindPRNumber implements FindPRNumberInterface
{
    public function __construct(private GithubAPIClientInterface $githubAPIClient)
    {
    }

    public function fetch(string $repository, string $commitRef): ?string
    {
        $searchResult = $this->searchResult($repository, $commitRef);

        return $this->prNumber($searchResult);
    }

    private function searchResult(string $repository, string $commitRef): array
    {
        $url = $this->searchUrl($repository, $commitRef);

        $response = $this->githubAPIClient->get($url, [], $repository);
        $content = json_decode($response->getBody()->getContents(), true);
        if (200 !== $response->getStatusCode() || null === $content) {
            throw new \RuntimeException(
                sprintf(
                    'There was a problem when fetching the check runs for repository "%s" (%s) and commit ref %s. Status "%s", content "%s"',
                    $repository,
                    $commitRef,
                    $url,
                    (string) $response->getStatusCode(),
                    $response->getBody()->getContents()
                )
            );
        }

        return $content;
    }

    private function searchUrl(string $repository, string $commitRef): string
    {
        return sprintf('https://api.github.com/repos/%s/commits/%s/pulls', $repository, $commitRef);
    }

    private function prNumber(array $searchResult): ?string
    {
        if (empty($searchResult)) {
            return null;
        }

        return isset(current($searchResult)['number']) ? (string) current($searchResult)['number'] :  null;
    }
}
