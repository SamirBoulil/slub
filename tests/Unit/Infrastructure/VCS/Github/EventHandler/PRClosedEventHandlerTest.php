<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\ClosePR\ClosePR;
use Slub\Application\ClosePR\ClosePRHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PRClosedEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRClosedEventHandlerTest extends TestCase
{
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /**
     * @var PRClosedEventHandler
     * @sut
     */
    private $PRCloseEventHandler;

    /** @var ObjectProphecy|ClosePRHandler */
    private $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(ClosePRHandler::class);
        $this->PRCloseEventHandler = new PRClosedEventHandler($this->handler->reveal());
    }

    /**
     * @test
     */
    public function it_only_listens_to_check_run_events()
    {
        self::assertTrue($this->PRCloseEventHandler->supports('pull_request'));
        self::assertFalse($this->PRCloseEventHandler->supports('unsupported_event'));
    }

    /**
     * @test
     */
    public function it_listens_to_close_PR_that_are_merged()
    {
        $closePREvent = [
            'pull_request' => [
                'number' => self::PR_NUMBER,
                'merged' => true,
            ],
            'repository'   => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(
                function (ClosePR $mergedPR) {
                    return self::PR_IDENTIFIER === $mergedPR->PRIdentifier
                        && self::REPOSITORY_IDENTIFIER === $mergedPR->repositoryIdentifier
                        && true === $mergedPR->isMerged;
                }
            )
        )->shouldBeCalled();

        $this->PRCloseEventHandler->handle($closePREvent);
    }


    /**
     * @test
     */
    public function it_listens_to_close_PR_that_are_not_merged()
    {
        $closePREvent = [
            'pull_request' => [
                'number' => self::PR_NUMBER,
                'merged' => false,
            ],
            'repository'   => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(
                function (ClosePR $mergedPR) {
                    return self::PR_IDENTIFIER === $mergedPR->PRIdentifier
                        && self::REPOSITORY_IDENTIFIER === $mergedPR->repositoryIdentifier
                        && false === $mergedPR->isMerged;
                }
            )
        )->shouldBeCalled();

        $this->PRCloseEventHandler->handle($closePREvent);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_merged()
    {
        $unsupportedEvent = ['dummy_event'];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->PRCloseEventHandler->handle($unsupportedEvent);
    }
}
