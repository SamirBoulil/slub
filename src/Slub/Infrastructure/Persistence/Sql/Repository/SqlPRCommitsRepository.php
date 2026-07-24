<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;

/**
 * Persists the association between a commit and the PR it belongs to (if any),
 * so that "status" events can be resolved to a PR number without calling the Github API.
 *
 * Only the head commit of a PR matters for resolving future "status" events, so
 * saveHeadCommit() evicts the other commits of the PR to keep the table minimal.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlPRCommitsRepository
{
    public const RETENTION_IN_DAYS = 30;

    public function __construct(private Connection $sqlConnection)
    {
    }

    /**
     * Records the head commit of a PR and evicts the PR's previous commits: the table
     * converges to one row per PR instead of one row per push.
     */
    public function saveHeadCommit(string $repositoryIdentifier, string $headCommitSha, string $PRNumber): void
    {
        $evictPreviousCommits = <<<SQL
DELETE FROM pr_commits
WHERE REPOSITORY_IDENTIFIER = :repository_identifier AND PR_NUMBER = :pr_number AND COMMIT_SHA != :commit_sha
;
SQL;
        $this->sqlConnection->executeStatement(
            $evictPreviousCommits,
            [
                'repository_identifier' => $repositoryIdentifier,
                'commit_sha' => $headCommitSha,
                'pr_number' => $PRNumber,
            ]
        );
        $this->save($repositoryIdentifier, $headCommitSha, $PRNumber);
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

    /**
     * Evicts the commits that have not been recorded for a while, to keep the table
     * size minimal. Returns the number of evicted commits.
     */
    public function evictStale(): int
    {
        return (int) $this->sqlConnection->executeStatement(
            sprintf('DELETE FROM pr_commits WHERE CREATED_AT < NOW() - INTERVAL %d DAY;', self::RETENTION_IN_DAYS)
        );
    }
}
