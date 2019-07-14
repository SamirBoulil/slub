<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
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

    public function fetch(string $channel, string $ts, string $userId): array
    {
        $reactions = $this->fetchReactions($channel, $ts);
        $botReactions = $this->findBotReactions($userId, $reactions);

        return $botReactions;
    }

    private function fetchReactions(string $channel, string $ts): array
    {
        $reactions = APIHelper::checkResponse(
            $this->client->get(
                'https://slack.com/api/reactions.get',
                [
                    'query' => [
                        'token' => $this->slackToken,
                        'channel' => $channel,
                        'timestamp' => $ts
                    ],
                ]
            )
        );

        return $reactions['message']['reactions'] ?? [];
    }

    private function findBotReactions(string $userId, array $reactions): array
    {
        return array_map(
            function (array $reaction) {
                return $reaction['name'];
            },
            array_filter(
                $reactions,
                function (array $reaction) use ($userId) {
                    return in_array($userId, $reaction['users']);
                }
            )
        );
    }
}
