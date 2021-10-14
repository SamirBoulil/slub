<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotUserId
{
    private ClientInterface $client;

    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    private ?string $cachedResult = null;

    public function __construct(ClientInterface $client, SqlSlackAppInstallationRepository $slackAppInstallationRepository)
    {
        $this->client = $client;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
    }

    public function fetch(string $workspaceId): string
    {
        if (null === $this->cachedResult) {
            $this->cachedResult = $this->fetchBotUserId($workspaceId);
        }

        return $this->cachedResult;
    }

    private function fetchBotUserId(string $workspaceId): string
    {
        $response = APIHelper::checkResponse(
            $this->client->get(
                'https://slack.com/api/bots.info',
                [
                    'query' => [
                        'token' => $this->slackToken($workspaceId),
                    ],
                ]
            )
        );

        return $response['bot']['id'];
    }

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
