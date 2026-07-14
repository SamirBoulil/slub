<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\Sql\Repository;

use Doctrine\DBAL\Connection;

/**
 * Persists Github API responses along with their ETag so they can be revalidated with
 * conditional requests: 304 responses do not count against the Github API rate limit.
 *
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlGithubAPIResponseCacheRepository
{
    public function __construct(private Connection $sqlConnection)
    {
    }

    public function save(string $url, string $etag, string $responseBody): void
    {
        $saveResponse = <<<SQL
INSERT INTO github_api_response_cache (URL_HASH, URL, ETAG, RESPONSE_BODY, REFRESHED_AT)
VALUES (:url_hash, :url, :etag, :response_body, NOW())
ON DUPLICATE KEY UPDATE
    ETAG = :etag,
    RESPONSE_BODY = :response_body,
    REFRESHED_AT = NOW()
;
SQL;
        $this->sqlConnection->executeStatement(
            $saveResponse,
            [
                'url_hash' => $this->urlHash($url),
                'url' => $url,
                'etag' => $etag,
                'response_body' => $responseBody,
            ]
        );
    }

    /**
     * Marks the cached response as still in use so the purge only evicts responses
     * that have not been served for a while.
     */
    public function touch(string $url): void
    {
        $touchResponse = <<<SQL
UPDATE github_api_response_cache
SET REFRESHED_AT = NOW()
WHERE URL_HASH = :url_hash
;
SQL;
        $this->sqlConnection->executeStatement($touchResponse, ['url_hash' => $this->urlHash($url)]);
    }

    /**
     * Returns null when there is no cached response for the url, otherwise an array
     * with the 'ETAG' and 'RESPONSE_BODY' keys.
     */
    public function find(string $url): ?array
    {
        $fetchResponse = <<<SQL
SELECT ETAG, RESPONSE_BODY
FROM github_api_response_cache
WHERE URL_HASH = :url_hash
;
SQL;
        $statement = $this->sqlConnection->executeQuery($fetchResponse, ['url_hash' => $this->urlHash($url)]);
        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            return null;
        }

        return ['ETAG' => (string) $result['ETAG'], 'RESPONSE_BODY' => (string) $result['RESPONSE_BODY']];
    }

    private function urlHash(string $url): string
    {
        return hash('sha256', $url);
    }
}
