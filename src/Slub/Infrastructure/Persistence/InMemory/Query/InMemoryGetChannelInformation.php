<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Query\ChannelInformation;
use Slub\Infrastructure\Chat\Slack\Query\GetChannelInformationInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class InMemoryGetChannelInformation implements GetChannelInformationInterface
{
    public function fetch(string $workspaceId, string $channelIdentifier): ChannelInformation
    {
        $channelInformation = new ChannelInformation();
        $channelInformation->channelName = 'akeneo';
        $channelInformation->channelIdentifier = $channelIdentifier;

        return $channelInformation;
    }
}
