<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Connection;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Repository\PRNotFoundException;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlGetMessageIdsForPR implements GetMessageIdsForPR
{
    /** @var Connection */
    private $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $messageIds = $this->fetchMessageIds($PRIdentifier);
        $messageIds = array_map(function (string $messageId) {
            return MessageIdentifier::fromString($messageId);
        }, $messageIds);

        return $messageIds;
    }

    private function fetchMessageIds(PRIdentifier $PRIdentifier): array
    {
        $sql = <<<SQL
SELECT MESSAGE_IDS
FROM pr
WHERE identifier = :identifier;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql, ['identifier' => $PRIdentifier->stringValue()]);
        $info = $statement->fetch(\PDO::FETCH_ASSOC);
        if (false === $info) {
            throw  PRNotFoundException::create($PRIdentifier);
        }
        $result = json_decode($info['MESSAGE_IDS'], true);

        return $result;
    }
}
