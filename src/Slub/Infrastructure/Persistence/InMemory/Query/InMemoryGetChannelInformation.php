<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Query\ChannelInformation;
use Slub\Domain\Query\GetChannelInformationInterface;
use Slub\Infrastructure\Chat\Slack\SlubBot;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class InMemoryGetChannelInformation implements GetChannelInformationInterface
{
    /** @var array */
    private $supportedChannels;

    public function __construct(string $commaSeparatedChannels)
    {
        $this->supportedChannels = explode(',', $commaSeparatedChannels);
    }

    public function fetch(ChannelIdentifier $channelIdentifier): ChannelInformation
    {
        $channelInformation = new ChannelInformation();
        $channelInformation->channelName = current($this->supportedChannels);
        $channelInformation->channelIdentifier = $channelIdentifier->stringValue();

        return $channelInformation;
    }
}
