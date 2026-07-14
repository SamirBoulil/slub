<?php

declare(strict_types=1);

namespace Tests;

use Psr\Http\Message\ResponseInterface;
use Slub\Infrastructure\VCS\Github\Client\GithubAPIClientInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GithubApiClientMock implements GithubAPIClientInterface
{
    /** @var string[] */
    private array $calledUrls = [];

    public function __construct(private array $stubs = [])
    {
    }

    public function get(string $url, array $options, $repositoryIdentifier): ResponseInterface
    {
        $this->calledUrls[] = $url;

        return $this->stubs[$url];
    }

    public function stubUrlWith($url, ResponseInterface $response): void
    {
        $this->stubs[$url] = $response;
    }

    /** @return string[] */
    public function calledUrls(): array
    {
        return $this->calledUrls;
    }
}
