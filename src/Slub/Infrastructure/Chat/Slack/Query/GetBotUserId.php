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
class GetBotUserId implements GetBotUserIdInterface
{
    private ?string $cachedResult = null;

    public function __construct(private ClientInterface $client, private SqlSlackAppInstallationRepository $slackAppInstallationRepository, private LoggerInterface $logger)
    {
    }

    public function fetch(string $workspaceId): string
    {
        if (null === $this->cachedResult) {
            $this->cachedResult = $this->fetchBotUserId($workspaceId);
        }

        $this->logger->critical(sprintf('Bot user id is "%s"', $this->cachedResult));

        return $this->cachedResult;
    }

    private function fetchBotUserId(string $workspaceId): string
    {
        $response = APIHelper::checkResponseSuccess(
            $this->client->post(
                'https://slack.com/api/auth.test',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->slackToken($workspaceId),
                    ],
                ]
            )
        );

        $this->logger->critical(json_encode($response));

        return $response['user_id'];
    }

    private function slackToken(string $workspaceId): string
    {
        return $this->slackAppInstallationRepository->getBy($workspaceId)->accessToken;
    }
}
