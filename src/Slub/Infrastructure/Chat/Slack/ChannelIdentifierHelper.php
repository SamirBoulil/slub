<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChannelIdentifierHelper
{
    private const SEPARATOR = '@';

    public static function from(string $workspace, string $channel): string
    {
        return sprintf('%s%s%s', $workspace, self::SEPARATOR, $channel);
    }

    public static function split(string $channelIdentifier): array
    {
        $message = explode(self::SEPARATOR, $channelIdentifier);
        $workspace = $message[0];
        $channel = $message[1];

        return ['workspace' => $workspace, 'channel' => $channel];
    }
}
