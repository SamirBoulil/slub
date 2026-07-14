<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlPRCommitsRepository;
use Slub\Infrastructure\VCS\Github\EventHandler\RecordPRCommitEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class RecordPRCommitEventHandlerTest extends TestCase
{
    use ProphecyTrait;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const COMMIT_SHA = 'commit-sha';
    private const PR_NUMBER = 10;

    /**
     * @sut
     */
    private RecordPRCommitEventHandler $recordPRCommitEventHandler;
    private SqlPRCommitsRepository|ObjectProphecy $prCommitsRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->prCommitsRepository = $this->prophesize(SqlPRCommitsRepository::class);
        $this->recordPRCommitEventHandler = new RecordPRCommitEventHandler($this->prCommitsRepository->reveal());
    }

    /**
     * @test
     * @dataProvider supportedActions
     */
    public function it_supports_pull_request_events_telling_the_head_commit_of_a_pr(string $action): void
    {
        self::assertTrue($this->recordPRCommitEventHandler->supports('pull_request', $this->event($action)));
    }

    /**
     * @test
     */
    public function it_does_not_support_other_events(): void
    {
        self::assertFalse($this->recordPRCommitEventHandler->supports('status', $this->event('opened')));
        self::assertFalse($this->recordPRCommitEventHandler->supports('pull_request', $this->event('closed')));
        self::assertFalse($this->recordPRCommitEventHandler->supports('pull_request', ['action' => 'opened']));
    }

    /**
     * @test
     */
    public function it_records_the_head_commit_of_the_pr(): void
    {
        $this->prCommitsRepository->save(self::REPOSITORY_IDENTIFIER, self::COMMIT_SHA, '10')->shouldBeCalled();

        $this->recordPRCommitEventHandler->handle($this->event('opened'));
    }

    public function supportedActions(): array
    {
        return [
            'opened' => ['opened'],
            'reopened' => ['reopened'],
            'synchronize' => ['synchronize'],
        ];
    }

    private function event(string $action): array
    {
        return [
            'action' => $action,
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
            'pull_request' => ['number' => self::PR_NUMBER, 'head' => ['sha' => self::COMMIT_SHA]],
        ];
    }
}
