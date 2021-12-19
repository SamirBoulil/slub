<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Common;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
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

    public static function workspaceFrom(ChannelIdentifier $channelIdentifier): string
    {
        return self::split($channelIdentifier->stringValue())[0];
    }

    public static function channelFrom(ChannelIdentifier $channelIdentifier): string
    {
        return self::split($channelIdentifier->stringValue())[1];
    }

    private static function split(string $channelIdentifier): array
    {
        Assert::contains($channelIdentifier, self::SEPARATOR, 'Impossible to split channel identifier');
        $splittedChannelIdentifier = explode(self::SEPARATOR, $channelIdentifier);
        Assert::count($splittedChannelIdentifier, 2);
        Assert::allStringNotEmpty($splittedChannelIdentifier);

        return $splittedChannelIdentifier;
    }
}
