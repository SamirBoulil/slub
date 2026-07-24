<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
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
    private LoggerInterface|ObjectProphecy $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->prCommitsRepository = $this->prophesize(SqlPRCommitsRepository::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->recordPRCommitEventHandler = new RecordPRCommitEventHandler(
            $this->prCommitsRepository->reveal(),
            $this->logger->reveal()
        );
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
        $this->prCommitsRepository->saveHeadCommit(self::REPOSITORY_IDENTIFIER, self::COMMIT_SHA, '10')->shouldBeCalled();

        $this->recordPRCommitEventHandler->handle($this->event('opened'));
    }

    /**
     * @test
     */
    public function it_only_logs_a_warning_when_recording_the_head_commit_fails(): void
    {
        $this->prCommitsRepository->saveHeadCommit(self::REPOSITORY_IDENTIFIER, self::COMMIT_SHA, '10')
            ->willThrow(new \RuntimeException('pr_commits is not writable'));
        $this->logger->warning(Argument::containingString('SamirBoulil/slub/10'))->shouldBeCalled();

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
