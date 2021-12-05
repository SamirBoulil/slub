<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotReactionsForMessageAndUser
{
    private ClientInterface $client;

    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        SqlSlackAppInstallationRepository $slackAppInstallationRepository,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
        $this->logger = $logger;
    }

    public function fetch(string $workspaceId, string $channel, string $ts, string $botId): array
    {
        $reactions = $this->fetchReactions($workspaceId, $channel, $ts);

        return $this->findBotReactions($botId, $reactions);
    }

    private function fetchReactions(string $workspaceId, string $channel, string $ts): array
    {
        $this->logger->critical(sprintf('Will get reactions for workspace "%s", channel "%s", message "%s"', $workspaceId, $channel, $ts));

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

        $this->logger->critical(sprintf('Reactions are: "%s"', json_encode($reactions)));

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
