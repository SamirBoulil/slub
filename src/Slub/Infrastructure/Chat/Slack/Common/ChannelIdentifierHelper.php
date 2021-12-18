<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Common;

use Webmozart\Assert\Assert;

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

    // TODO: Consider introducing 2 methods for that like (::slackWorkspace ::slackChannelIdentifier)
    public static function split(string $channelIdentifier): array
    {
        Assert::contains($channelIdentifier, self::SEPARATOR, 'Impossible to split channel identifier');
        $message = explode(self::SEPARATOR, $channelIdentifier);
        Assert::count($message, 2);
        $workspace = $message[0];
        $channel = $message[1];

        return ['workspace' => $workspace, 'channel' => $channel];
    }
}
