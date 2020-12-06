<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotReactionsForMessageAndUser
{
    private ClientInterface $client;

    private string $slackToken;

    public function __construct(ClientInterface $client, string $slackToken)
    {
        $this->client = $client;
        $this->slackToken = $slackToken;
    }

    public function fetch(string $channel, string $ts, string $userId): array
    {
        $reactions = $this->fetchReactions($channel, $ts);

        return $this->findBotReactions($userId, $reactions);
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
            fn (array $reaction) => $reaction['name'],
            array_filter(
                $reactions,
                fn (array $reaction) => in_array($userId, $reaction['users'])
            )
        );
    }
}
