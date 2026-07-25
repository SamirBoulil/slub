<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Query;

use GuzzleHttp\ClientInterface;
use Slub\Infrastructure\Chat\Slack\Common\APIHelper;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetMessagePermalink
{
    public function __construct(private ClientInterface $client, private SqlSlackAppInstallationRepository $slackAppInstallationRepository)
    {
    }

    public function fetch(string $workspaceId, string $channel, string $ts): string
    {
        $response = APIHelper::checkResponseSuccess(
            $this->client->get(
                'https://slack.com/api/chat.getPermalink',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->slackToken($workspaceId),
                    ],
                    'query' => [
                        'channel' => $channel,
                        'message_ts' => $ts
                    ],
                ]
            )
        );

        return $response['permalink'];
    }

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
