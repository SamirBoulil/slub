<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Query\ChannelInformation;
use Slub\Domain\Query\GetChannelInformationInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 */
class GetChannelInformation implements GetChannelInformationInterface
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

    public function fetch(ChannelIdentifier $channelIdentifier): ChannelInformation
    {
        $response = $this->client->post(
            'https://slack.com/api/channels.info',
            [
                'form_params' => [
                    'token'   => $this->slackToken,
                    'channel' => $channelIdentifier->stringValue(),
                ],
            ]
        );
        $channel = $this->parseResponse($response);
        $channelInformation = new ChannelInformation();
        $channelInformation->channelIdentifier = $channelIdentifier->stringValue();
        $channelInformation->channelName = $channel['channel']['name'];

        return $channelInformation;
    }

    private function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $contents = json_decode($response->getBody()->getContents(), true);
        $hasError = 200 !== $statusCode || false === $contents['ok'] || !isset($contents['channel']);

        if ($hasError) {
            throw new \RuntimeException(
                sprintf(
                    'There was an issue when retrieving the channel information (status %d): "%s"',
                    $statusCode,
                    json_encode($contents)
                )
            );
        }

        return $contents;
    }
}
