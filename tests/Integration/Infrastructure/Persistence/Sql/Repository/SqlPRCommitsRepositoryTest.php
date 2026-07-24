<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Sql\Repository;

use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class SqlPRCommitsRepositoryTest extends KernelTestCase
{
    private const REPOSITORY_IDENTIFIER = 'akeneo/pim-community-dev';

    private SqlPRCommitsRepository $prCommitsRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->prCommitsRepository = $this->get('slub.infrastructure.persistence.pr_commits_repository');
    }

    /** @test */
    public function it_saves_a_pr_commit_and_finds_it(): void
    {
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'commit_sha_with_pr', '1234');

        $actual = $this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'commit_sha_with_pr');

        self::assertSame(['PR_NUMBER' => '1234'], $actual);
    }

    /** @test */
    public function it_saves_a_commit_belonging_to_no_pr_and_finds_it(): void
    {
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'commit_sha_without_pr', null);

        $actual = $this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'commit_sha_without_pr');

        self::assertSame(['PR_NUMBER' => null], $actual);
    }

    /** @test */
    public function it_returns_null_when_the_commit_is_unknown(): void
    {
        self::assertNull($this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'unknown_commit_sha'));
    }

    /** @test */
    public function it_updates_the_pr_number_of_a_commit(): void
    {
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'commit_sha', null);
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'commit_sha', '42');

        $actual = $this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'commit_sha');

        self::assertSame(['PR_NUMBER' => '42'], $actual);
    }

    /** @test */
    public function it_keeps_only_the_head_commit_of_a_pr(): void
    {
        $this->prCommitsRepository->saveHeadCommit(self::REPOSITORY_IDENTIFIER, 'head_of_another_pr', '99');
        $this->prCommitsRepository->saveHeadCommit(self::REPOSITORY_IDENTIFIER, 'first_head_sha', '77');

        $this->prCommitsRepository->saveHeadCommit(self::REPOSITORY_IDENTIFIER, 'new_head_sha', '77');

        self::assertNull($this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'first_head_sha'));
        self::assertSame(['PR_NUMBER' => '77'], $this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'new_head_sha'));
        self::assertSame(['PR_NUMBER' => '99'], $this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'head_of_another_pr'));
    }

    /** @test */
    public function it_evicts_the_stale_commits(): void
    {
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'stale_commit_sha', '11');
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'fresh_commit_sha', '12');
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $connection->executeUpdate(
            'UPDATE pr_commits SET CREATED_AT = :stale WHERE REPOSITORY_IDENTIFIER = :repository_identifier AND COMMIT_SHA = :commit_sha',
            ['stale' => '2020-01-01 00:00:00', 'repository_identifier' => self::REPOSITORY_IDENTIFIER, 'commit_sha' => 'stale_commit_sha']
        );

        $this->prCommitsRepository->evictStale();

        self::assertNull($this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'stale_commit_sha'));
        self::assertSame(['PR_NUMBER' => '12'], $this->prCommitsRepository->find(self::REPOSITORY_IDENTIFIER, 'fresh_commit_sha'));
    }

    /** @test */
    public function it_refreshes_the_creation_date_when_saving_an_already_known_commit(): void
    {
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'commit_sha_still_in_use', '42');
        $connection = $this->get('slub.infrastructure.persistence.sql.database_connection');
        $connection->executeUpdate(
            'UPDATE pr_commits SET CREATED_AT = :stale WHERE REPOSITORY_IDENTIFIER = :repository_identifier AND COMMIT_SHA = :commit_sha',
            ['stale' => '2020-01-01 00:00:00', 'repository_identifier' => self::REPOSITORY_IDENTIFIER, 'commit_sha' => 'commit_sha_still_in_use']
        );

        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, 'commit_sha_still_in_use', '42');

        $createdAt = $connection
            ->executeQuery(
                'SELECT CREATED_AT FROM pr_commits WHERE REPOSITORY_IDENTIFIER = :repository_identifier AND COMMIT_SHA = :commit_sha',
                ['repository_identifier' => self::REPOSITORY_IDENTIFIER, 'commit_sha' => 'commit_sha_still_in_use']
            )
            ->fetch(\PDO::FETCH_COLUMN);
        self::assertGreaterThan('2020-01-01 00:00:00', $createdAt);
    }
}
