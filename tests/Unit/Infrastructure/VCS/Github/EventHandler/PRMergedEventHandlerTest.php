<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\MergedPR\MergedPR;
use Slub\Application\MergedPR\MergedPRHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PRMergedEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class PRMergedEventHandlerTest extends TestCase
{
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /**
     * @var PRMergedEventHandler
     * @sut
     */
    private $PRMergedEventHandler;

    /** @var ObjectProphecy|MergedPRHandler */
    private $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(MergedPRHandler::class);
        $this->PRMergedEventHandler = new PRMergedEventHandler($this->handler->reveal());
    }

    /**
     * @test
     */
    public function it_only_listens_to_check_run_events()
    {
        self::assertTrue($this->PRMergedEventHandler->supports('pull_request'));
        self::assertFalse($this->PRMergedEventHandler->supports('unsupported_event'));
    }

    /**
     * @test
     */
    public function it_listens_to_merged_PR()
    {
        $mergedPREvent = [
            'pull_request' => [
                'number' => self::PR_NUMBER,
                'merged' => true,
            ],
            'repository'   => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(
                function (MergedPR $mergedPR)
                {
                    return self::PR_IDENTIFIER === $mergedPR->PRIdentifier
                        && self::REPOSITORY_IDENTIFIER === $mergedPR->repositoryIdentifier;
                }
            )
        )->shouldBeCalled();

        $this->PRMergedEventHandler->handle($mergedPREvent);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_merged()
    {
        $unsupportedEvent = ['dummy_event'];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->PRMergedEventHandler->handle($unsupportedEvent);
    }
}
