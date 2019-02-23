<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

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

    public function __construct(array $supportedChannels)
    {
        $this->supportedChannels = $supportedChannels;
    }

    public function fetch(string $channelId): ChannelInformation
    {
        $channelInformation = new ChannelInformation();
        $channelInformation->channelName = current($this->supportedChannels);
        $channelInformation->channelId = $channelId;

        return $channelInformation;
    }

    public function setSlubBot(SlubBot $slubBot): void
    {

    }
}
