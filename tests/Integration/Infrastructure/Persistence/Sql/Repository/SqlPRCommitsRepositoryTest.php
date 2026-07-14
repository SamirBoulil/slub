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
}
