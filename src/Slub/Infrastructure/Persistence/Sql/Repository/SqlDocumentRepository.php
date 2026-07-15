<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Types\Type;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Repository\DocumentRepositoryInterface;

class SqlDocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(private Connection $sqlConnection)
    {
    }

    public function save(Document $document): void
    {
        $sql = <<<SQL
INSERT INTO
  documents (IDENTIFIER, URL, AUTHOR_IDENTIFIER, CHANNEL_IDS, WORKSPACE_IDS, MESSAGE_IDS, PUT_TO_REVIEW_AT)
VALUES
  (:IDENTIFIER, :URL, :AUTHOR_IDENTIFIER, :CHANNEL_IDS, :WORKSPACE_IDS, :MESSAGE_IDS, :PUT_TO_REVIEW_AT)
ON DUPLICATE KEY UPDATE
  URL = :URL,
  AUTHOR_IDENTIFIER = :AUTHOR_IDENTIFIER,
  CHANNEL_IDS = :CHANNEL_IDS,
  WORKSPACE_IDS = :WORKSPACE_IDS,
  MESSAGE_IDS = :MESSAGE_IDS,
  PUT_TO_REVIEW_AT = :PUT_TO_REVIEW_AT;
SQL;

        $this->sqlConnection->executeUpdate(
            $sql,
            $document->normalize(),
            [
                'CHANNEL_IDS' => 'json',
                'WORKSPACE_IDS' => 'json',
                'MESSAGE_IDS' => 'json',
            ]
        );
    }

    /**
     * @return Document[]
     */
    public function all(): array
    {
        return array_map(
            fn (array $normalizedDocument) => $this->hydrate($normalizedDocument),
            $this->fetchAll()
        );
    }

    public function reset(): void
    {
        $this->sqlConnection->executeUpdate('DELETE FROM documents;');
    }

    private function fetchAll(): array
    {
        $sql = <<<SQL
SELECT IDENTIFIER, URL, AUTHOR_IDENTIFIER, CHANNEL_IDS, WORKSPACE_IDS, MESSAGE_IDS, PUT_TO_REVIEW_AT
FROM documents;
SQL;
        $statement = $this->sqlConnection->executeQuery($sql);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @throws DBALException
     */
    private function hydrate(array $result): Document
    {
        $result['CHANNEL_IDS'] = null !== $result['CHANNEL_IDS'] ? json_decode($result['CHANNEL_IDS'], true) : [];
        $result['WORKSPACE_IDS'] = null !== $result['WORKSPACE_IDS'] ? json_decode($result['WORKSPACE_IDS'], true) : [];
        $result['MESSAGE_IDS'] = null !== $result['MESSAGE_IDS'] ? json_decode($result['MESSAGE_IDS'], true) : [];
        $result['URL'] = Type::getType(Type::STRING)->convertToPHPValue(
            $result['URL'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['AUTHOR_IDENTIFIER'] = Type::getType(Type::STRING)->convertToPHPValue(
            $result['AUTHOR_IDENTIFIER'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $result['PUT_TO_REVIEW_AT'] = Type::getType(Type::STRING)->convertToPHPValue(
            $result['PUT_TO_REVIEW_AT'],
            $this->sqlConnection->getDatabasePlatform()
        );

        return Document::fromNormalized($result);
    }
}
