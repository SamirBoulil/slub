<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SqlSlackAppInstallationRepository
{
    private Connection $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    public function save(SlackAppInstallation $slackAppInstallation): void
    {
        $saveAccessToken = <<<SQL
INSERT INTO slack_app_installation (WORKSPACE_ID, ACCESS_TOKEN)
VALUES (:workspace_id, :access_token)
ON DUPLICATE KEY UPDATE
    WORKSPACE_ID = :workspace_id,
    ACCESS_TOKEN = :access_token
;
SQL;
        $this->sqlConnection->executeStatement(
            $saveAccessToken,
            ['workspace_id' => $slackAppInstallation->workspaceId, 'access_token' => $slackAppInstallation->accessToken]
        );
    }

    public function getBy(string $workspaceId): SlackAppInstallation
    {
        $fetchAppInstallation = <<<SQL
SELECT ACCESS_TOKEN
FROM slack_app_installation
WHERE WORKSPACE_ID = :workspace_id
;
SQL;
        $statement = $this->sqlConnection->executeQuery(
            $fetchAppInstallation,
            ['workspace_id' => $workspaceId]
        );
        $accessToken = $statement->fetch(\PDO::FETCH_COLUMN);
        if (false === $accessToken) {
            throw new \RuntimeException(
                sprintf('There was no slack app access token found for workspace %s', $workspaceId)
            );
        }

        $result = new SlackAppInstallation();
        $result->workspaceId = $workspaceId;
        $result->accessToken = $accessToken;

        return $result;
    }
}
