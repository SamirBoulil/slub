<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;

/**
 * Repository only used for the infrastructure layer, to make sure we only treat a delivered event once.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlDeliveredEventRepository
{
    /** @var Connection */
    private $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    /**
     * @throws \RuntimeException
     */
    public function save(string $deliveryId): void
    {
        $sql = <<<SQL
INSERT IGNORE INTO delivered_event
SET IDENTIFIER = :deliveredEventId;
SQL;

        $this->sqlConnection->executeUpdate($sql, ['deliveredEventId' => $deliveryId]);
    }

    /**
     * @throws \RuntimeException
     */
    public function getBy(string $deliveryEventId): string
    {
        $sql = <<<SQL
SELECT IDENTIFIER
FROM delivered_event
WHERE IDENTIFIER = :deliveredEventId;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql, ['deliveredEventId' => $deliveryEventId]);
        $deliveryEventId = $statement->fetch(\PDO::FETCH_COLUMN);
        if (!$deliveryEventId) {
            throw new \RuntimeException(sprintf('Delivered event with identifier "%s" does not exist', $deliveryEventId));
        }

        return $deliveryEventId;
    }
}
