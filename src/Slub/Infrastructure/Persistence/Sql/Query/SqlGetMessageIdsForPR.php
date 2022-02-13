<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Query;

use Doctrine\DBAL\Driver\Connection;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetMessageIdsForPR;
use Slub\Domain\Repository\PRNotFoundException;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SqlGetMessageIdsForPR implements GetMessageIdsForPR
{
    public function __construct(private Connection $sqlConnection)
    {
    }

    public function fetch(PRIdentifier $PRIdentifier): array
    {
        $messageIds = $this->fetchMessageIds($PRIdentifier);

        return array_map(fn (string $messageId) => MessageIdentifier::fromString($messageId), $messageIds);
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

        return json_decode($info['MESSAGE_IDS'], true);
    }
}
