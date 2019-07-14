<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Psr\Http\Message\ResponseInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class APIHelper
{
    public static function checkResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $contents = json_decode($response->getBody()->getContents(), true);
        $hasError = 200 !== $statusCode || false === $contents['ok'];

        if ($hasError) {
            throw new \RuntimeException(
                sprintf(
                    'There was an issue when communicating with the slack API (status %d): "%s"',
                    $statusCode,
                    json_encode($contents)
                )
            );
        }

        return $contents;
    }
}
