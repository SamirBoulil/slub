<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GithubAPIHelper
{
    public static function authorizationHeader(string $authToken): array
    {
        return ['Authorization' => sprintf('token %s', $authToken)];
    }

    public static function acceptPreviewEndpointsHeader(): array
    {
        return ['Accept' => 'application/vnd.github.antiope-preview+json'];
    }

    public static function breakoutPRIdentifier(PRIdentifier $PRIdentifier): array
    {
        preg_match('/(.+)\/(.+)\/(.+)/', $PRIdentifier->stringValue(), $matches);
        array_shift($matches);
        Assert::count($matches, 3);

        return $matches;
    }
}
