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

    public function __construct(Client $client, string $slackToken)
    {
        $this->client = $client;
        $this->slackToken = $slackToken;
    }

    public function fetch(): string
    {
        if (null === $this->cachedResult) {
            $this->cachedResult = $this->fetchBotUserId();
        }

        return $this->cachedResult;
    }

    private function fetchBotUserId(): string
    {
        $response = APIHelper::checkResponse(
            $this->client->post(
                'https://slack.com/api/users.identity',
                [
                    'form_params' => [
                        'token' => $this->slackToken,
                    ],
                ]
            )
        );

        return $response['user']['id'];
    }
}
