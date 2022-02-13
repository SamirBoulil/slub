<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Query;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Chat\Slack\Common\APIHelper;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotReactionsForMessageAndUser
{
    public function __construct(private ClientInterface $client, private SqlSlackAppInstallationRepository $slackAppInstallationRepository, private LoggerInterface $logger)
    {
    }

    public function fetch(string $workspaceId, string $channel, string $ts, string $botId): array
    {
        $reactions = $this->fetchReactions($workspaceId, $channel, $ts);

        return $this->findBotReactions($botId, $reactions);
    }

    private function fetchReactions(string $workspaceId, string $channel, string $ts): array
    {
        $reactions = APIHelper::checkResponseSuccess(
            $this->client->get(
                'https://slack.com/api/reactions.get',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken($workspaceId),
                    ],
                    'query' => [
                        'channel' => $channel,
                        'timestamp' => $ts
                    ],
                ]
            )
        );

        return $reactions['message']['reactions'] ?? [];
    }

    private function findBotReactions(string $botId, array $reactions): array
    {
        return array_map(
            fn (array $reaction) => $reaction['name'],
            array_filter(
                $reactions,
                fn (array $reaction) => in_array($botId, $reaction['users'])
            )
        );
    }

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
