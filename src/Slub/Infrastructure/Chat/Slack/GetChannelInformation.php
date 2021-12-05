<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

use GuzzleHttp\ClientInterface;
use Slub\Domain\Query\ChannelInformation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetChannelInformation implements GetChannelInformationInterface
{
    private ClientInterface $client;

    private SqlSlackAppInstallationRepository $slackAppInstallationRepository;

    public function __construct(ClientInterface $client, SqlSlackAppInstallationRepository $slackAppInstallationRepository)
    {
        $this->client = $client;
        $this->slackAppInstallationRepository = $slackAppInstallationRepository;
    }

    public function fetch(string $workspaceId, string $channelIdentifier): ChannelInformation
    {
        $response = $this->client->post(
            'https://slack.com/api/conversations.info',
            [
                'form_params' => [
                    'token' => $this->slackToken($workspaceId),
                    'channel' => $channelIdentifier,
                ],
            ]
        );
        $channel = APIHelper::checkResponseSuccess($response);
        $channelInformation = new ChannelInformation();
        $channelInformation->channelIdentifier = $channelIdentifier;
        $channelInformation->channelName = $channel['channel']['name'];

        return $channelInformation;
    }

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
