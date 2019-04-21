<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Query\GetReviewCountForPR;
use Slub\Domain\Repository\PRNotFoundException;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SqlGetReviewCountForPR implements GetReviewCountForPR
{
    /** @var Connection */
    private $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    public function fetch(PRIdentifier $PRIdentifier): int
    {
        $sql = <<<SQL
SELECT (GTMS + NOT_GTMS) as REVIEW_COUNT
FROM pr
WHERE identifier = :identifier;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql, ['identifier' => $PRIdentifier->stringValue()]);
        $result = $statement->fetch(\PDO::FETCH_COLUMN);
        if (false === $result) {
            throw PRNotFoundException::create($PRIdentifier);
        }

        return (int) $result;
    }
}
