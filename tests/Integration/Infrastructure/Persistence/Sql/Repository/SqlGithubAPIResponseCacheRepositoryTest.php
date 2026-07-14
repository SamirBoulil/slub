<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Slub\Infrastructure\Persistence\Sql\Repository\SqlGithubAPIResponseCacheRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlGithubAPIResponseCacheRepositoryTest extends KernelTestCase
{
    private SqlGithubAPIResponseCacheRepository $responseCacheRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->responseCacheRepository = $this->get('slub.infrastructure.persistence.github_api_response_cache_repository');
    }

    /** @test */
    public function it_saves_a_response_and_finds_it_by_url(): void
    {
        $url = 'https://api.github.com/repos/samirboulil/slub/pulls/10';

        $this->responseCacheRepository->save($url, 'W/"an_etag"', '{"title": "A PR"}');

        self::assertSame(
            ['ETAG' => 'W/"an_etag"', 'RESPONSE_BODY' => '{"title": "A PR"}'],
            $this->responseCacheRepository->find($url)
        );
    }

    /** @test */
    public function it_supports_urls_longer_than_255_characters(): void
    {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/commits/%s/check-runs',
            str_repeat('a', 150),
            str_repeat('b', 150),
            str_repeat('c', 40)
        );

        $this->responseCacheRepository->save($url, 'W/"an_etag"', '{"check_runs": []}');

        self::assertSame(
            ['ETAG' => 'W/"an_etag"', 'RESPONSE_BODY' => '{"check_runs": []}'],
            $this->responseCacheRepository->find($url)
        );
    }

    /** @test */
    public function it_returns_null_when_there_is_no_cached_response_for_the_url(): void
    {
        self::assertNull($this->responseCacheRepository->find('https://api.github.com/unknown'));
    }

    /** @test */
    public function it_touches_a_cached_response_to_mark_it_as_still_in_use(): void
    {
        $url = 'https://api.github.com/repos/samirboulil/slub/pulls/12';
        $this->responseCacheRepository->save($url, 'W/"an_etag"', '{"title": "A PR"}');
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $connection->executeUpdate(
            'UPDATE github_api_response_cache SET REFRESHED_AT = :stale WHERE URL = :url',
            ['stale' => '2020-01-01 00:00:00', 'url' => $url]
        );

        $this->responseCacheRepository->touch($url);

        $refreshedAt = $connection
            ->executeQuery('SELECT REFRESHED_AT FROM github_api_response_cache WHERE URL = :url', ['url' => $url])
            ->fetch(\PDO::FETCH_COLUMN);
        self::assertGreaterThan('2020-01-01 00:00:00', $refreshedAt);
    }

    /** @test */
    public function it_updates_a_cached_response(): void
    {
        $url = 'https://api.github.com/repos/samirboulil/slub/pulls/11';
        $this->responseCacheRepository->save($url, 'W/"an_etag"', '{"title": "A PR"}');

        $this->responseCacheRepository->save($url, 'W/"a_new_etag"', '{"title": "An updated PR"}');

        self::assertSame(
            ['ETAG' => 'W/"a_new_etag"', 'RESPONSE_BODY' => '{"title": "An updated PR"}'],
            $this->responseCacheRepository->find($url)
        );
    }
}
