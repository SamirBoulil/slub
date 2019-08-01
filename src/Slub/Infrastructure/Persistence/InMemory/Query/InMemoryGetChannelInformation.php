<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Query\ChannelInformation;
use Slub\Infrastructure\Chat\Slack\GetChannelInformationInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>

 */
class InMemoryGetChannelInformation implements GetChannelInformationInterface
{
    /** @var array */
    private $supportedChannels;

    public function __construct(string $commaSeparatedChannels)
    {
        $this->supportedChannels = explode(',', $commaSeparatedChannels);
    }

    public function fetch(string $channelIdentifier): ChannelInformation
    {
        $channelInformation = new ChannelInformation();
        $channelInformation->channelName = current($this->supportedChannels);
        $channelInformation->channelIdentifier = $channelIdentifier;

        return $channelInformation;
    }
}
