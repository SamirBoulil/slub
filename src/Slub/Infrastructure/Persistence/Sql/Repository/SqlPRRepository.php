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

    public function reset(): void
    {
        $this->sqlConnection->executeUpdate(
            sprintf('CREATE DATABASE IF NOT EXISTS %s;', $this->sqlConnection->getDatabase())
        );
        $this->sqlConnection->executeUpdate('DROP TABLE IF EXISTS pr');
        $createTable = <<<SQL
CREATE TABLE pr (
	IDENTIFIER VARCHAR(255) PRIMARY KEY,
	GTMS INT(11) DEFAULT 0,
	NOT_GTMS INT(11) DEFAULT 0,
	CI_STATUS VARCHAR(255) NOT NULL,
	IS_MERGED BOOLEAN DEFAULT false,
	MESSAGE_IDS JSON
);
SQL;
        $this->sqlConnection->executeUpdate($createTable);
    }

    /**
     * @throws PRNotFoundException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fetch(PRIdentifier $PRidentifier): array
    {
        $sql = <<<SQL
SELECT IDENTIFIER, GTMS, NOT_GTMS, CI_STATUS, IS_MERGED, MESSAGE_IDS
FROM PR
WHERE identifier = :identifier;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql, ['identifier' => $PRidentifier->stringValue()]);
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            throw PRNotFoundException::create($PRidentifier);
        }

        return $result;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function hydrate(array $result): PR
    {
        $result['MESSAGE_IDS'] = json_decode($result['MESSAGE_IDS'], true);
        $result['GTMS'] = Type::getType(Type::INTEGER)->convertToPhpValue($result['GTMS'],
            $this->sqlConnection->getDatabasePlatform());
        $result['NOT_GTMS'] = Type::getType(Type::INTEGER)->convertToPhpValue($result['NOT_GTMS'],
            $this->sqlConnection->getDatabasePlatform());
        $result['IS_MERGED'] = Type::getType(Type::BOOLEAN)->convertToPhpValue($result['IS_MERGED'],
            $this->sqlConnection->getDatabasePlatform());

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
INSERT INTO pr (IDENTIFIER, GTMS, NOT_GTMS, CI_STATUS, IS_MERGED, MESSAGE_IDS)
VALUES (:IDENTIFIER, :GTMS, :NOT_GTMS, :CI_STATUS, :IS_MERGED, :MESSAGE_IDS);
SQL;
        $this->sqlConnection->executeUpdate($sql, $PR->normalize(), ['MESSAGE_IDS' => 'json']);
    }
}
