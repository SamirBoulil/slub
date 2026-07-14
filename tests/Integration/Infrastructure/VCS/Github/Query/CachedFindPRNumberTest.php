<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\Query;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;
use Slub\Infrastructure\VCS\Github\Query\CachedFindPRNumber;
use Slub\Infrastructure\VCS\Github\Query\FindPRNumberInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CachedFindPRNumberTest extends TestCase
{
    use ProphecyTrait;
    private const REPOSITORY = 'SamirBoulil/slub';
    private const COMMIT_REF = 'commit-ref';
    private const PR_NUMBER = '10';

    /**
     * @sut
     */
    private CachedFindPRNumber $cachedFindPRNumber;
    private SqlPRCommitsRepository|ObjectProphecy $prCommitsRepository;
    private FindPRNumberInterface|ObjectProphecy $findPRNumber;

    public function setUp(): void
    {
        parent::setUp();
        $this->prCommitsRepository = $this->prophesize(SqlPRCommitsRepository::class);
        $this->findPRNumber = $this->prophesize(FindPRNumberInterface::class);
        $this->cachedFindPRNumber = new CachedFindPRNumber(
            $this->prCommitsRepository->reveal(),
            $this->findPRNumber->reveal(),
            new NullLogger()
        );
    }

    /** @test */
    public function it_returns_the_cached_pr_number_without_calling_the_github_api(): void
    {
        $this->prCommitsRepository->find(self::REPOSITORY, self::COMMIT_REF)
            ->willReturn(['PR_NUMBER' => self::PR_NUMBER]);
        $this->findPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF)->shouldNotBeCalled();

        self::assertSame(self::PR_NUMBER, $this->cachedFindPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF));
    }

    /** @test */
    public function it_finds_the_pr_number_from_the_github_api_and_caches_it_when_the_commit_is_unknown(): void
    {
        $this->prCommitsRepository->find(self::REPOSITORY, self::COMMIT_REF)->willReturn(null);
        $this->findPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF)->willReturn(self::PR_NUMBER);
        $this->prCommitsRepository->save(self::REPOSITORY, self::COMMIT_REF, self::PR_NUMBER)->shouldBeCalled();

        self::assertSame(self::PR_NUMBER, $this->cachedFindPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF));
    }

    /** @test */
    public function it_asks_the_github_api_again_when_the_commit_had_no_pr_as_one_can_be_opened_later(): void
    {
        $this->prCommitsRepository->find(self::REPOSITORY, self::COMMIT_REF)
            ->willReturn(['PR_NUMBER' => null]);
        $this->findPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF)->willReturn(self::PR_NUMBER);
        $this->prCommitsRepository->save(self::REPOSITORY, self::COMMIT_REF, self::PR_NUMBER)->shouldBeCalled();

        self::assertSame(self::PR_NUMBER, $this->cachedFindPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF));
    }

    /** @test */
    public function it_does_not_cache_the_absence_of_pr(): void
    {
        $this->prCommitsRepository->find(self::REPOSITORY, self::COMMIT_REF)->willReturn(null);
        $this->findPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF)->willReturn(null);
        $this->prCommitsRepository->save(self::REPOSITORY, self::COMMIT_REF, null)->shouldNotBeCalled();

        self::assertNull($this->cachedFindPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF));
    }

    /** @test */
    public function it_falls_back_to_the_github_api_when_the_cache_read_fails(): void
    {
        $this->prCommitsRepository->find(self::REPOSITORY, self::COMMIT_REF)
            ->willThrow(new \RuntimeException('Cache is broken'));
        $this->findPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF)->willReturn(self::PR_NUMBER);
        $this->prCommitsRepository->save(self::REPOSITORY, self::COMMIT_REF, self::PR_NUMBER)->shouldBeCalled();

        self::assertSame(self::PR_NUMBER, $this->cachedFindPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF));
    }

    /** @test */
    public function it_still_returns_the_pr_number_when_the_cache_write_fails(): void
    {
        $this->prCommitsRepository->find(self::REPOSITORY, self::COMMIT_REF)->willReturn(null);
        $this->findPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF)->willReturn(self::PR_NUMBER);
        $this->prCommitsRepository->save(self::REPOSITORY, self::COMMIT_REF, self::PR_NUMBER)
            ->willThrow(new \RuntimeException('Cache is broken'));

        self::assertSame(self::PR_NUMBER, $this->cachedFindPRNumber->fetch(self::REPOSITORY, self::COMMIT_REF));
    }
}
