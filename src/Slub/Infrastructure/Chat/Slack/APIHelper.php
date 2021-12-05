<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Psr\Http\Message\ResponseInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class APIHelper
{
    public static function checkResponseSuccess(ResponseInterface $response): array
    {
        self::checkStatusCodeSuccess($response);
        $contents = json_decode($response->getBody()->getContents(), true);
        $hasError = false === $contents['ok'];
        if ($hasError) {
            throw new \RuntimeException(
                sprintf(
                    'There was an issue when communicating with the slack API (status %d): "%s"',
                    $response->getStatusCode(),
                    json_encode($contents)
                )
            );
        }

        return $contents;
    }

    public static function checkStatusCodeSuccess(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        $hasError = 200 !== $statusCode;
        if ($hasError) {
            throw new \RuntimeException(
                sprintf(
                    'There was an issue when communicating with the slack API (status %d)',
                    $statusCode,
                )
            );
        }
    }
}
