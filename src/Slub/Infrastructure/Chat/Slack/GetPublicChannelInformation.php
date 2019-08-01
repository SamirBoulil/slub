<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Slub\Domain\Query\ChannelInformation;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>

 */
class GetPublicChannelInformation implements GetChannelInformationInterface
{
    /** @var Client */
    private $client;

    /** @var string */
    private $slackToken;

    public function __construct(Client $client, string $slackToken)
    {
        $this->client = $client;
        $this->slackToken = $slackToken;
    }

    public function fetch(string $channelIdentifier): ChannelInformation
    {
        $response = $this->client->post(
            'https://slack.com/api/channels.info',
            [
                'form_params' => [
                    'token' => $this->slackToken,
                    'channel' => $channelIdentifier,
                ],
            ]
        );
        $channel = APIHelper::checkResponse($response);
        $channelInformation = new ChannelInformation();
        $channelInformation->channelIdentifier = $channelIdentifier;
        $channelInformation->channelName = $channel['channel']['name'];

        return $channelInformation;
    }
}
