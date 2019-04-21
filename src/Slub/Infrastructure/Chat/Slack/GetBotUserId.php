<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class GetBotUserId
{
    /** @var Client */
    private $client;

    /** @var string */
    private $slackToken;

    /** @var ?string */
    private $cachedResult;

    /** @var string */
    private $botUserId;

    public function __construct(Client $client, string $slackToken, string $botUserId)
    {
        $this->client = $client;
        $this->slackToken = $slackToken;
        $this->botUserId = $botUserId;
    }

    public function fetch(): string
    {
        return $this->botUserId;

//        if (null === $this->cachedResult) {
//            $this->cachedResult = $this->fetchBotUserId();
//        }
//
//        return $this->cachedResult;
    }

//    private function fetchBotUserId(): string
//    {
//        $response = APIHelper::checkResponse(
//            $this->client->get(
//                'https://slack.com/api/bots.info',
//                [
//                    'query' => [
//                        'token' => $this->slackToken,
//                        'bot' => '',
//                    ],
//                ]
//            )
//        );
//
//        return $response['bot']['id'];
//    }
}
