<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sentry\State\HubInterface;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerInterface;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerRegistry;
use Slub\Infrastructure\VCS\Github\EventHandler\NewEventAction;
use Slub\Infrastructure\VCS\Github\EventHandler\ProcessGithubEventsAsync;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ProcessGithubEventsAsyncTest extends TestCase
{
    use ProphecyTrait;
    private const EVENT_TYPE = 'EVENT_TYPE';

    /**
     * @sut
     */
    private ProcessGithubEventsAsync $processGithubEventsAsync;
    private EventHandlerRegistry|ObjectProphecy $eventHandlerRegistry;
    private HubInterface|ObjectProphecy $sentryHub;
    private LoggerInterface|ObjectProphecy $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->eventHandlerRegistry = $this->prophesize(EventHandlerRegistry::class);
        $this->sentryHub = $this->prophesize(HubInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->processGithubEventsAsync = new ProcessGithubEventsAsync(
            $this->eventHandlerRegistry->reveal(),
            $this->sentryHub->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * @test
     */
    public function it_processes_flagged_github_events_on_kernel_terminate(): void
    {
        $eventPayload = ['payload'];
        $request = $this->flaggedRequest($eventPayload);
        $eventHandler = $this->prophesize(EventHandlerInterface::class);
        $eventHandler->handle($eventPayload)->shouldBeCalled();
        $this->eventHandlerRegistry->get(self::EVENT_TYPE, $eventPayload)->willReturn([$eventHandler->reveal()]);

        $this->processGithubEventsAsync->onKernelTerminate($this->terminateEvent($request));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_request_has_no_github_event_to_process(): void
    {
        $request = new Request([], [], [], [], [], [], (string) json_encode(['payload']));
        $this->eventHandlerRegistry->get(Argument::cetera())->shouldNotBeCalled();

        $this->processGithubEventsAsync->onKernelTerminate($this->terminateEvent($request));
    }

    /**
     * @test
     */
    public function it_reports_a_failing_event_handler_and_runs_the_remaining_ones(): void
    {
        $eventPayload = ['payload'];
        $request = $this->flaggedRequest($eventPayload);
        $handlerError = new \RuntimeException('Some handler error');
        $failingEventHandler = $this->prophesize(EventHandlerInterface::class);
        $failingEventHandler->handle($eventPayload)->willThrow($handlerError);
        $eventHandler = $this->prophesize(EventHandlerInterface::class);
        $eventHandler->handle($eventPayload)->shouldBeCalled();
        $this->eventHandlerRegistry->get(self::EVENT_TYPE, $eventPayload)
            ->willReturn([$failingEventHandler->reveal(), $eventHandler->reveal()]);
        $this->sentryHub->captureException($handlerError)->shouldBeCalled()->willReturn(null);
        $this->logger->critical(
            Argument::containingString('Some handler error'),
            ['exception' => $handlerError]
        )->shouldBeCalled();

        $this->processGithubEventsAsync->onKernelTerminate($this->terminateEvent($request));
    }

    private function flaggedRequest(array $eventPayload): Request
    {
        $request = new Request([], [], [], [], [], [], (string) json_encode($eventPayload));
        $request->headers->set('X-GitHub-Event', self::EVENT_TYPE);
        $request->attributes->set(NewEventAction::PROCESS_EVENT_ATTRIBUTE, true);

        return $request;
    }

    private function terminateEvent(Request $request): TerminateEvent
    {
        return new TerminateEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            new Response()
        );
    }
}
