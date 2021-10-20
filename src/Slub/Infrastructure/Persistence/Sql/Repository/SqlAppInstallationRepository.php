<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Slub\Infrastructure\VCS\Github\Client\GithubAppInstallation;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlAppInstallationRepository
{
    private Connection $sqlConnection;

    public function __construct(Connection $sqlConnection)
    {
        $this->sqlConnection = $sqlConnection;
    }

    public function save(GithubAppInstallation $appInstallation): void
    {
        $saveAccessToken = <<<SQL
INSERT INTO app_installation (REPOSITORY_IDENTIFIER, INSTALLATION_ID, ACCESS_TOKEN)
VALUES (:repository_identifier, :installation_id, :access_token)
ON DUPLICATE KEY UPDATE
    REPOSITORY_IDENTIFIER = :repository_identifier,
    INSTALLATION_ID = :installation_id,
    ACCESS_TOKEN = :access_token
;
SQL;
        $this->sqlConnection->executeStatement(
            $saveAccessToken,
            [
                'repository_identifier' => $appInstallation->repositoryIdentifier,
                'installation_id' => $appInstallation->installationId,
                'access_token' => $appInstallation->accessToken,
            ]
        );
    }

    public function getBy(string $repositoryIdentifier): GithubAppInstallation
    {
        $result = $this->fetch($repositoryIdentifier);

        return $this->hydrate($result);
    }

    private function hydrate(array $result): GithubAppInstallation
    {
        $appInstallation = new GithubAppInstallation();
        $appInstallation->repositoryIdentifier = Type::getType(Type::STRING)->convertToPHPValue(
            $result['REPOSITORY_IDENTIFIER'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $appInstallation->installationId = Type::getType(Type::STRING)->convertToPHPValue(
            $result['INSTALLATION_ID'],
            $this->sqlConnection->getDatabasePlatform()
        );
        $appInstallation->accessToken = Type::getType(Type::STRING)->convertToPHPValue(
            $result['ACCESS_TOKEN'],
            $this->sqlConnection->getDatabasePlatform()
        );

        return $appInstallation;
    }

    /**
     * @throws DBALException
     */
    private function fetch(string $repositoryIdentifier): array
    {
        $fetchAppInstallation = <<<SQL
SELECT REPOSITORY_IDENTIFIER, INSTALLATION_ID, ACCESS_TOKEN
FROM app_installation
WHERE REPOSITORY_IDENTIFIER = :repository_identifier
;
SQL;
        $statement = $this->sqlConnection->executeQuery(
            $fetchAppInstallation,
            ['repository_identifier' => $repositoryIdentifier]
        );
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            throw new \RuntimeException(
                sprintf('There was no app installation found for repository %s', $repositoryIdentifier)
            );
        }

        return $result;
    }
}
