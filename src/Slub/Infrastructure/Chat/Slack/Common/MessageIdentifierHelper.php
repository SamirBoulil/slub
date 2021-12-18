<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Common;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class MessageIdentifierHelper
{
    private const SEPARATOR = '@';

    public static function from(string $workspace, string $channel, string $ts): string
    {
        return sprintf('%s%s%s%s%s', $workspace, self::SEPARATOR, $channel, self::SEPARATOR, $ts);
    }

    // TODO: Consider introducing ::channelFrom ::workspaceFrom tsFrom::
    public static function split(string $messageIdentifier): array
    {
        $message = explode(self::SEPARATOR, $messageIdentifier);
        $workspace = $message[0];
        $channel = $message[1];
        $ts = $message[2];

        return ['workspace' => $workspace, 'channel' => $channel, 'ts' => $ts];
    }
}
