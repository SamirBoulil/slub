<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\CIStatusUpdate\WarnLargePR;
use Slub\Application\NewReview\NewReview;
use Slub\Application\WarnLargePR\WarnLargePRHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PRLargeEventHandler;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRLargeEventHandlerTest extends TestCase
{
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /**
     * @var PRLargeEventHandler
     * @sut
     */
    private $prLargeEventHandler;

    /** @var ObjectProphecy|WarnLargePRHandler */
    private $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(WarnLargePRHandler::class);
        $this->prLargeEventHandler = new PRLargeEventHandler($this->handler->reveal());
    }

    /**
     * @test
     */
    public function it_only_listens_to_pull_request_events(): void
    {
        self::assertTrue($this->prLargeEventHandler->supports('pull_request'));
        self::assertFalse($this->prLargeEventHandler->supports('pull_request_review'));
        self::assertFalse($this->prLargeEventHandler->supports('unsupported_event'));
    }

    /**
     * @test
     */
    public function it_listens_to_large_PR(): void
    {
        $largePR = [
            'action' => 'submitted',
            'pull_request' => ['number' => self::PR_NUMBER, 'user' => ['id' => 1, 'login' => 'lucie'], 'additions' => 501, 'deletions' => 0],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(
                fn (WarnLargePR $warnLargePR) => self::PR_IDENTIFIER === $warnLargePR->PRIdentifier
                    && self::REPOSITORY_IDENTIFIER === $warnLargePR->repositoryIdentifier
                    && 501 === $warnLargePR->additions
                    && 0 === $warnLargePR->deletions
            )
        )->shouldBeCalled();

        $this->prLargeEventHandler->handle($largePR);
    }

    /**
     * @test
     */
    public function it_does_not_warn_large_pr_from_small_pr(): void
    {
        $smallPR = [
            'action' => 'submitted',
            'pull_request' => ['number' => self::PR_NUMBER, 'user' => ['id' => 1, 'login' => 'lucie'], 'additions' => 500, 'deletions' => 500],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->prLargeEventHandler->handle($smallPR);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_merged(): void
    {
        $unsupportedEvent = ['dummy_event'];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->prLargeEventHandler->handle($unsupportedEvent);
    }
}