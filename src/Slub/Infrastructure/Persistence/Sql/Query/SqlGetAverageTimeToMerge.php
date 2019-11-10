<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Slub\Domain\Query\GetAverageTimeToMergeInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlGetAverageTimeToMerge implements GetAverageTimeToMergeInterface
{
    /** @var Connection */
    private $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    public function fetch(): ?int
    {
        $query = <<<SQL
SELECT FLOOR(AVG(TIMESTAMPDIFF(DAY, FROM_UNIXTIME(PUT_TO_REVIEW_AT), FROM_UNIXTIME(CLOSED_AT)))) average_time_to_merge
FROM pr
WHERE
    IS_MERGED is TRUE
    AND rows_before_migration_Version20190609163730 IS FALSE
;
SQL;
        $stmt = $this->sqlConnection->executeQuery($query);
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);

        if ($this->isResultEmpty($result)) {
            return null;
        }

        return $this->convertToInteger($result);
    }

    private function isResultEmpty($result): bool
    {
        return null === $result;
    }

    private function convertToInteger($result): int
    {
        return Type::getType(Type::INTEGER)
            ->convertToPhpValue(
                $result,
                $this->sqlConnection->getDatabasePlatform()
            );
    }
}
