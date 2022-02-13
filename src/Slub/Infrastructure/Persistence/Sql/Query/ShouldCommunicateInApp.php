<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 * Note: this class has never been used, it's the foundation of PLG program that let's
 * the bot communicate in APP news about features etc.
 */
class ShouldCommunicateInApp
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function messageAboutNewSlashCommand(string $workspaceId, string $userId): bool
    {
        return 0 === $this->fetchCommunicationCount($workspaceId, $userId);
    }

    private function fetchCommunicationCount(string $workspaceId, string $userId): int
    {
        $query = <<<SQL
SELECT NEW_SLASH_COMMAND_RELEASE_COMMUNICATION_COUNT FROM user_in_app_communication
WHERE WORKSPACE_ID = :workspace_id AND USER_ID = :user_id;
SQL;

        $statement = $this->connection->executeQuery(
            $query,
            ['workspace_id' => $workspaceId, 'user_id' => $userId]
        );

        $result = $statement->fetchColumn();
        if (false === $result) {
            return 1;
        }

        return (int) $result;
    }
}
