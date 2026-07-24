<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;

/**
 * Persists the association between a commit and the PR it belongs to (if any),
 * so that "status" events can be resolved to a PR number without calling the Github API.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlPRCommitsRepository
{
    public function __construct(private Connection $sqlConnection)
    {
    }

    public function save(string $repositoryIdentifier, string $commitSha, ?string $PRNumber): void
    {
        $savePRCommit = <<<SQL
INSERT INTO pr_commits (REPOSITORY_IDENTIFIER, COMMIT_SHA, PR_NUMBER)
VALUES (:repository_identifier, :commit_sha, :pr_number)
ON DUPLICATE KEY UPDATE
    PR_NUMBER = :pr_number,
    CREATED_AT = CURRENT_TIMESTAMP
;
SQL;
        $this->sqlConnection->executeStatement(
            $savePRCommit,
            [
                'repository_identifier' => $repositoryIdentifier,
                'commit_sha' => $commitSha,
                'pr_number' => $PRNumber,
            ]
        );
    }

    /**
     * Returns null when the commit is unknown, otherwise an array with a 'PR_NUMBER' key
     * holding the PR number the commit belongs to, or null when the commit is known to
     * belong to no PR.
     */
    public function find(string $repositoryIdentifier, string $commitSha): ?array
    {
        $fetchPRCommit = <<<SQL
SELECT PR_NUMBER
FROM pr_commits
WHERE REPOSITORY_IDENTIFIER = :repository_identifier AND COMMIT_SHA = :commit_sha
;
SQL;
        $statement = $this->sqlConnection->executeQuery(
            $fetchPRCommit,
            [
                'repository_identifier' => $repositoryIdentifier,
                'commit_sha' => $commitSha,
            ]
        );
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            return null;
        }

        return ['PR_NUMBER' => null !== $result['PR_NUMBER'] ? (string) $result['PR_NUMBER'] : null];
    }
}
