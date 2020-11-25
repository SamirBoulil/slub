<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlHasEventAlreadyBeenDelivered
{
    /** @var Connection */
    private $sqlConnection;

    public function __construct(\Doctrine\DBAL\Driver\Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    public function fetch(string $deliveredEventIdentifier): bool
    {
        $sql = <<<SQL
        SELECT EXISTS (
            SELECT 1
            FROM delivered_event
            WHERE IDENTIFIER = :deliveredEventIdentifier 
        ) as is_existing
SQL;

        $statement = $this->sqlConnection->executeQuery($sql, ['deliveredEventIdentifier' => $deliveredEventIdentifier]);

        return (bool) $statement->fetch(\PDO::FETCH_COLUMN);
    }
}
