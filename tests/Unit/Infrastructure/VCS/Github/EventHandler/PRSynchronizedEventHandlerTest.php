<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Query\GetPRInfoInterface;
use Slub\Domain\Query\PRInfo;
use Slub\Infrastructure\VCS\Github\EventHandler\PRSynchronizedEventHandler;
use Slub\Infrastructure\VCS\Github\Query\CIStatus\CIStatus;
use Slub\Infrastructure\VCS\Github\Query\GetPRInfo;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PRSynchronizedEventHandlerTest extends TestCase
{
    use ProphecyTrait;
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';
    private const CI_STATUS = 'PENDING';

    /**
     * @sut
     */
    private PRSynchronizedEventHandler $PRSynchronizedEventHandler;

    private CIStatusUpdateHandler|ObjectProphecy $handler;

    private GetPRInfoInterface|ObjectProphecy $getPRInfo;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(CIStatusUpdateHandler::class);
        $this->getPRInfo = $this->prophesize(GetPRInfo::class);
        $this->PRSynchronizedEventHandler = new PRSynchronizedEventHandler($this->handler->reveal());
    }

    /**
     * @test
     */
    public function it_only_listens_to_pull_request_events(): void
    {
        self::assertTrue($this->PRSynchronizedEventHandler->supports('pull_request', []));
        self::assertFalse($this->PRSynchronizedEventHandler->supports('unsupported_event', []));
    }

    /**
     * @test
     */
    public function it_listens_to_PR_that_are_synchronized(): void
    {
        $PRSynchronizedEvent = [
            'action' => 'synchronize',
            'pull_request' => ['number' => self::PR_NUMBER],
            'repository'   => ['full_name' => self::REPOSITORY_IDENTIFIER],
        ];

        $this->handler->handle(
            Argument::that(fn (CIStatusUpdate $command) => self::PR_IDENTIFIER === $command->PRIdentifier
                && self::REPOSITORY_IDENTIFIER === $command->repositoryIdentifier
                && self::CI_STATUS === $command->status
                && empty($command->buildLink))
        )->shouldBeCalled();

        $this->PRSynchronizedEventHandler->handle($PRSynchronizedEvent);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_the_pr_is_not_synchronized(): void
    {
        $unsupportedEvent = ['dummy_event'];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->PRSynchronizedEventHandler->handle($unsupportedEvent);
    }
}
