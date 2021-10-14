<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotReactionsForMessageAndUser
{
    private ClientInterface $client;

    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    public function __construct(ClientInterface $client, SqlSlackAppInstallationRepository $slackAppInstallationRepository)
    {
        $this->client = $client;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
    }

    public function fetch(string $workspaceId, string $channel, string $ts, string $userId): array
    {
        $reactions = $this->fetchReactions($workspaceId, $channel, $ts);

        return $this->findBotReactions($userId, $reactions);
    }

    private function fetchReactions(string $workspaceId, string $channel, string $ts): array
    {
        $reactions = APIHelper::checkResponse(
            $this->client->get(
                'https://slack.com/api/reactions.get',
                [
                    'query' => [
                        'token' => $this->slackToken($workspaceId),
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

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
