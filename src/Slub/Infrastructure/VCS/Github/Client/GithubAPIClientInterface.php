<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Client;

use Psr\Http\Message\ResponseInterface;

interface GithubAPIClientInterface
{
    public function get(string $url, array $options, $repositoryIdentifier): ResponseInterface;
}
