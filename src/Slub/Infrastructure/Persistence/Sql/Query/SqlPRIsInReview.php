<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Driver\Connection;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\PRIsInReview;

/**
 * Checks a PR is in review.
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2024 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SqlPRIsInReview implements PRIsInReview
{
    public function __construct(private Connection $sqlConnection)
    {
    }

    public function fetch(PRIdentifier $PRIdentifier): bool
    {
        $sql = <<<SQL
        SELECT EXISTS (
            SELECT 1
            FROM pr
            WHERE IDENTIFIER = :PRIdentifier
        ) as is_existing
SQL;

        $statement = $this->sqlConnection->executeQuery($sql, ['PRIdentifier' => $PRIdentifier->stringValue()]);

        return (bool) $statement->fetch(\PDO::FETCH_COLUMN);
    }
}
