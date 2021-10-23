<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\ChangePRSize\ChangePRSize;
use Slub\Application\ChangePRSize\ChangePRSizeHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PRSizeChangedEventHandler;

/**
 * @author    Pierrick Martos <pierrick.martos@gmail.com>
 */
class PRSizeChangedEventHandlerTest extends TestCase
{
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /**
     * @var PRSizeChangedEventHandler
     * @sut
     */
    private $prLargeEventHandler;

    /** @var ObjectProphecy|ChangePRSizeHandler */
    private $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(ChangePRSizeHandler::class);
        $this->prLargeEventHandler = new PRSizeChangedEventHandler($this->handler->reveal());
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
            'pull_request' => [
                'number' => self::PR_NUMBER,
                'user' => ['id' => 1, 'login' => 'lucie'],
                'additions' => 501,
                'deletions' => 0,
            ],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(
                static fn (ChangePRSize $changePRSize): bool => self::PR_IDENTIFIER === $changePRSize->PRIdentifier
                    && 501 === $changePRSize->additions
                    && 0 === $changePRSize->deletions
            )
        )->shouldBeCalled();

        $this->prLargeEventHandler->handle($largePR);
    }

    /**
     * @test
     */
    public function it_listens_to_small_PR(): void
    {
        $smallPR = [
            'pull_request' => [
                'number' => self::PR_NUMBER,
                'user' => ['id' => 1, 'login' => 'lucie'],
                'additions' => 400,
                'deletions' => 400,
            ],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(
                fn (ChangePRSize $changePR) => self::PR_IDENTIFIER === $changePR->PRIdentifier
                    && 400 === $changePR->additions
                    && 400 === $changePR->deletions
            )
        )->shouldBeCalled();

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
