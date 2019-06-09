<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SqlPRRepository implements PRRepositoryInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Connection */
    private $sqlConnection;

    public function __construct(Connection $sqlConnection, EventDispatcherInterface $eventDispatcher)
    {
        $this->sqlConnection = $sqlConnection;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function save(PR $PR): void
    {
        $this->updatePR($PR);
        $this->dispatchEvents($PR);
    }

    public function getBy(PRIdentifier $PRidentifier): PR
    {
        $result = $this->fetch($PRidentifier);

        return $this->hydrate($result);
    }

    /**
     * @return PR[]
     */
    public function all(): array
    {
        $result = $this->fetchAll();

        return array_map(
            function (array $normalizedPR)
            {
                return $this->hydrate($normalizedPR);
            },
            $result
        );
    }

    public function reset(): void
    {
        $this->sqlConnection->executeUpdate('DELETE FROM pr;');
    }

    /**
     * @throws PRNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetch(PRIdentifier $PRidentifier): array
    {
        $sql = <<<SQL
SELECT IDENTIFIER, GTMS, NOT_GTMS, COMMENTS, CI_STATUS, IS_MERGED, MESSAGE_IDS, PUT_TO_REVIEW_AT, MERGED_AT
FROM pr
WHERE identifier = :identifier;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql, ['identifier' => $PRidentifier->stringValue()]);
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            throw PRNotFoundException::create($PRidentifier);
        }

        return $result;
    }

    private function fetchAll(): array
    {
        $sql = <<<SQL
SELECT IDENTIFIER, GTMS, NOT_GTMS, COMMENTS, CI_STATUS, IS_MERGED, MESSAGE_IDS, PUT_TO_REVIEW_AT, MERGED_AT
FROM pr
ORDER BY IS_MERGED ASC;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql);
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function hydrate(array $result): PR
    {
        $result['MESSAGE_IDS'] = json_decode($result['MESSAGE_IDS'], true);
        $result['GTMS'] = Type::getType(Type::INTEGER)->convertToPhpValue(
            $result['GTMS'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['NOT_GTMS'] = Type::getType(Type::INTEGER)->convertToPhpValue(
            $result['NOT_GTMS'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['COMMENTS'] = Type::getType(Type::INTEGER)->convertToPhpValue(
            $result['COMMENTS'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['IS_MERGED'] = Type::getType(Type::BOOLEAN)->convertToPhpValue(
            $result['IS_MERGED'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['PUT_TO_REVIEW_AT'] = Type::getType(Type::STRING)->convertToPHPValue(
            $result['PUT_TO_REVIEW_AT'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['MERGED_AT'] = Type::getType(Type::STRING)->convertToPHPValue(
            $result['MERGED_AT'],
            $this->sqlConnection->getDatabasePlatform()
        );

        return PR::fromNormalized($result);
    }

    private function dispatchEvents(PR $PR): void
    {
        foreach ($PR->getEvents() as $event) {
            $this->eventDispatcher->dispatch(get_class($event), $event);
        }
    }

    /**
     * @param PR $PR
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updatePR(PR $PR): void
    {
        $sql = <<<SQL
INSERT INTO
  pr (IDENTIFIER, GTMS, NOT_GTMS, COMMENTS, CI_STATUS, IS_MERGED, MESSAGE_IDS, PUT_TO_REVIEW_AT, MERGED_AT)
VALUES
  (:IDENTIFIER, :GTMS, :NOT_GTMS, :COMMENTS, :CI_STATUS, :IS_MERGED, :MESSAGE_IDS, :PUT_TO_REVIEW_AT, :MERGED_AT)
ON DUPLICATE KEY UPDATE
  IDENTIFIER = :IDENTIFIER,
  GTMS = :GTMS,
  NOT_GTMS = :NOT_GTMS,
  COMMENTS = :COMMENTS,
  CI_STATUS = :CI_STATUS,
  IS_MERGED = :IS_MERGED,
  MESSAGE_IDS = :MESSAGE_IDS,
  PUT_TO_REVIEW_AT = :PUT_TO_REVIEW_AT,
  MERGED_AT = :MERGED_AT;
SQL;

        $this->sqlConnection->executeUpdate(
            $sql,
            $PR->normalize(),
            [
                'IS_MERGED'   => 'boolean',
                'MESSAGE_IDS' => 'json',
            ]
        );
    }
}
