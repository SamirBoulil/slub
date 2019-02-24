<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use Slub\Domain\Query\ChannelInformation;
use Slub\Domain\Query\GetChannelInformationInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class GetChannelInformation implements GetChannelInformationInterface
{
    /** @var SlubBot */
    private $slubBot;

    /**
     * @Refactor: Find a solution for this
     */
    public function setSlubBot(SlubBot $slubBot): void
    {
        $this->slubBot = $slubBot;
    }

    public function fetch(string $channelId): ChannelInformation
    {
        /** @var Response $getChannelInfoResponse */
        $getChannelInfoResponse = $this->slubBot->getBot()->sendRequest('channels.info', ['channel' => $channelId]);
        $channel = json_decode($getChannelInfoResponse->getContent(), true);

        if (!$channel['ok']) {
            throw new \LogicException('There was an issue when getting the channel name from the Slack API');
        }

        $channelInformation = new ChannelInformation();
        $channelInformation->channelId = $channelId;
        $channelInformation->channelName = $channel['channel']['name'];

        return $channelInformation;
    }
}
