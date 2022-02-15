<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Common;

use Psr\Http\Message\ResponseInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class APIHelper
{
    public static function checkResponseSuccess(ResponseInterface $response): array
    {
        self::checkStatusCodeSuccess($response);
        $contents = self::parseContents($response);

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

    private static function parseContents(ResponseInterface $response): mixed
    {
        $contents = json_decode($response->getBody()->getContents(), true);
        $hasError = false === $contents['ok'];
        if ($hasError) {
            if ('not_in_channel' === ($contents['error'] ?? '')) {
                throw new BotNotInChannelException();
            }

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
}
