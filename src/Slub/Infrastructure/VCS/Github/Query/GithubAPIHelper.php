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

    public static function PRIdentifierFrom(string $repositoryIdentifier, string $PRNumber): PRIdentifier
    {
        return PRIdentifier::fromString(sprintf('%s/%s', $repositoryIdentifier, $PRNumber));
    }

    public static function PRUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);

        return sprintf('https://github.com/%s/%s/pull/%s', ...$matches);
    }

    public static function PRAPIUrl(PRIdentifier $PRIdentifier): string
    {
        $matches = GithubAPIHelper::breakoutPRIdentifier($PRIdentifier);

        return sprintf('https://api.github.com/repos/%s/%s/pulls/%s', ...$matches);
    }

    public static function repositoryIdentifierFrom(PRIdentifier $PRIdentifier): string
    {
        $matches = self::breakoutPRIdentifier($PRIdentifier);

        return sprintf('%s/%s', $matches[0], $matches[1]);
    }

    public static function PRNumber(PRIdentifier $PRIdentifier): string
    {
        return self::breakoutPRIdentifier($PRIdentifier)[2];
    }

    public static function acceptMachineManPreviewHeader(): array
    {
        return ['Accept' => 'application/vnd.github.machine-man-preview+json'];
    }

    private static function breakoutPRIdentifier(PRIdentifier $PRIdentifier): array
    {
        $PRIdentifierToBrekaout = $PRIdentifier->stringValue();
        preg_match('/(.+)\/(.+)\/(.+)/', $PRIdentifierToBrekaout, $matches);
        array_shift($matches);
        Assert::count($matches, 3, sprintf('Impossible to breakout PRIdentifier "%s"', $PRIdentifierToBrekaout));

        return $matches;
    }
}
