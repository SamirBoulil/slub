<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
*/
class GetBotReactionsForMessageAndUser
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

    public function fetch(string $ts, string $userId): array
    {
        $reactions = $this->fetchReactions($ts);
        $botReactions = $this->findBotReactions($userId, $reactions);

        return $botReactions;
    }

    private function fetchReactions(string $ts): array
    {
        $response = $this->client->post(
            'https://slack.com/api/reactions.get',
            [
                'form_params' => [
                    'token' => $this->slackToken,
                    'timestamp' => $ts
                ],
            ]
        );
        $reactions = APIHelper::checkResponse($response);

        return $reactions['message']['reactions'];
    }

    private function findBotReactions(string $userId, array $reactions): array
    {
        return array_filter(
            $reactions,
            function (array $reaction) use ($userId) {
                return in_array($userId, $reaction['users']);
            }
        );
    }
}
