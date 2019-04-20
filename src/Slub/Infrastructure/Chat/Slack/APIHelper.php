<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Psr\Http\Message\ResponseInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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
                    'There was an issue when retrieving the reactions for a message (status %d): "%s"',
                    $statusCode,
                    json_encode($contents)
                )
            );
        }

        return $contents;
    }
}
