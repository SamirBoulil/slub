<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Query;

use GuzzleHttp\ClientInterface;
use Slub\Infrastructure\Chat\Slack\Common\APIHelper;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetReactionsForMessage
{
    public function __construct(private ClientInterface $client, private SqlSlackAppInstallationRepository $slackAppInstallationRepository)
    {
    }

    /**
     * @return array<array{name: string, count: int, users: string[]}>
     */
    public function fetch(string $workspaceId, string $channel, string $ts): array
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

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
