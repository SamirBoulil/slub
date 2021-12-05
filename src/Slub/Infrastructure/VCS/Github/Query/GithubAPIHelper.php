<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\Query;

use Slub\Domain\Entity\PR\PRIdentifier;
use Webmozart\Assert\Assert;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GithubAPIHelper
{
    public static function authorizationHeader(string $authToken): array
    {
        return ['Authorization' => sprintf('token %s', $authToken)];
    }

    public static function authorizationHeaderWithJWT(string $jwt): array
    {
        return ['Authorization' => sprintf('Bearer %s', $jwt)];
    }

    public static function acceptPreviewEndpointsHeader(): array
    {
        return ['Accept' => 'application/vnd.github.antiope-preview+json'];
    }

    // TODO: Consider making this private and use clearer repository() and PRNumber() functions instead
    public static function breakoutPRIdentifier(PRIdentifier $PRIdentifier): array
    {
        preg_match('/(.+)\/(.+)\/(.+)/', $PRIdentifier->stringValue(), $matches);
        array_shift($matches);
        Assert::count($matches, 3);

        return $matches;
    }

    public static function PRIdentifierFrom(string $repositoryIdentifier, string $PRNumber): string
    {
        return sprintf('%s/%s', $repositoryIdentifier, $PRNumber);
    }

    public static function PRPageUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);

        return sprintf('https://github.com/%s/%s/pull/%s', ...$matches);
    }

    public static function PRAPIUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);

        return sprintf('https://api.github.com/repos/%s/%s/pulls/%s', ...$matches);
    }

    public static function acceptMachineManPreviewHeader(): array
    {
        return ['Accept' => 'application/vnd.github.machine-man-preview+json'];
    }
}
