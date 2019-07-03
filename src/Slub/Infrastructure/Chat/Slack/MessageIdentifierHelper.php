<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class MessageIdentifierHelper
{
    private const SEPARATOR = '@';

    public static function from(string $channel, string $ts): string
    {
        return sprintf('%s%s%s', $channel, self::SEPARATOR, $ts);
    }

    public static function split(string $messageIdentifier): array
    {
        $message = explode(self::SEPARATOR, $messageIdentifier);
        $channel = $message[0];
        $ts = $message[1];

        return ['channel' => $channel, 'ts' => $ts];
    }
}
