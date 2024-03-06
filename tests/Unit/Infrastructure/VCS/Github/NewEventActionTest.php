<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Slub\Infrastructure\Persistence\Sql\Query\SqlHasEventAlreadyBeenDelivered;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlDeliveredEventRepository;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerInterface;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerRegistry;
use Slub\Infrastructure\VCS\Github\EventHandler\NewEventAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class NewEventActionTest extends TestCase
{
    use ProphecyTrait;
    private const SECRET = 'SECRET';
    private const DELIVERY_EVENT_IDENTIFIER = '1234';

    /**
     * @sut
     */
    private NewEventAction $newEventAction;
    private EventHandlerRegistry|ObjectProphecy $eventHandlerRegistry;
    private ObjectProphecy|SqlDeliveredEventRepository $deliveredEventRepository;
    private ObjectProphecy|SqlHasEventAlreadyBeenDelivered $hasEventAlreadyBeenDelivered;
    private ObjectProphecy|LoggerInterface $logger;

    public function setUp(): void
    {
        $this->eventHandlerRegistry = $this->prophesize(EventHandlerRegistry::class);
        $this->hasEventAlreadyBeenDelivered = $this->prophesize(SqlHasEventAlreadyBeenDelivered::class);
        $this->deliveredEventRepository = $this->prophesize(SqlDeliveredEventRepository::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->newEventAction = new NewEventAction(
            $this->eventHandlerRegistry->reveal(),
            $this->hasEventAlreadyBeenDelivered->reveal(),
            $this->deliveredEventRepository->reveal(),
            $this->logger->reveal(),
            self::SECRET
        );
    }

    /**
     * @test
     */
    public function it_successfully_processes_supported_events(): void
    {
        $eventType = 'EVENT_TYPE';
        $eventPayload = ['payload'];
        $supportedRequest = $this->supportedRequest($eventType, $eventPayload, self::DELIVERY_EVENT_IDENTIFIER);
        $eventHandler = $this->prophesize(EventHandlerInterface::class);
        $eventHandler->handle($eventPayload)->shouldBeCalled();
        $this->eventHandlerRegistry->get($eventType)->willReturn([$eventHandler->reveal()]);
        $this->hasEventAlreadyBeenDelivered->fetch(self::DELIVERY_EVENT_IDENTIFIER)->willReturn(false);
        $this->deliveredEventRepository->save(self::DELIVERY_EVENT_IDENTIFIER)->shouldBeCalled();

        $this->newEventAction->executeAction($supportedRequest);
    }

    /**
     * @test
     * @dataProvider wrongRequests
     */
    public function it_throws(Request $wrongRequest): void
    {
        $eventType = 'EVENT_TYPE';
        $eventPayload = ['payload'];
        $supportedRequest = $this->supportedRequest($eventType, $eventPayload, self::DELIVERY_EVENT_IDENTIFIER);
        $this->hasEventAlreadyBeenDelivered->fetch(self::DELIVERY_EVENT_IDENTIFIER)->willReturn(false);
        $this->eventHandlerRegistry->get($eventType)->willReturn([]);

        $this->expectException(\Exception::class);
        $this->newEventAction->executeAction($wrongRequest);
    }

    /**
     * @test
     */
    public function it_throws_if_the_event_has_already_been_processed(): void
    {
        $alreadyDeliveredRequest = $this->supportedRequest('UNKNOWN', ['payload'], self::DELIVERY_EVENT_IDENTIFIER);

        $this->expectException(\TypeError::class);
        $this->newEventAction->executeAction($alreadyDeliveredRequest);
    }

//    /**
//     * @test
//     */
//    public function it_logs_if_the_event_type_is_unsupported(): void
//    {
//        $eventType = 'UNSUPPORTED_EVENT_TYPE';
//        $eventPayload = ['payload'];
//        $supportedRequest = $this->supportedRequest($eventType, $eventPayload, self::DELIVERY_EVENT_IDENTIFIER);
//        $this->eventHandlerRegistry->get($eventType)->willReturn([]);
//        $this->hasEventAlreadyBeenDelivered->fetch(self::DELIVERY_EVENT_IDENTIFIER)->willReturn(false);
//        // $this->logger->critical(Argument::cetera())->shouldBeCalled();
//        // $this->logger->log(LogLevel::NOTICE, Argument::any())->ShouldBeCalled();
//
//        $this->newEventAction->executeAction($supportedRequest);
//    }

//    /**
//     * @test
//     */
//    public function it_logs_if_the_event_has_already_been_delivered(): void
//    {
//        $alreadyDeliveredRequest = $this->supportedRequest('EVENT_TYPE', ['payload'], self::DELIVERY_EVENT_IDENTIFIER);
//        $this->hasEventAlreadyBeenDelivered->fetch(self::DELIVERY_EVENT_IDENTIFIER)->willReturn(true);
//
//        $this->newEventAction->executeAction($alreadyDeliveredRequest);
//
//        // $this->logger->notice(sprintf('Event has already been delivered "%s"', self::DELIVERY_EVENT_IDENTIFIER))->shouldBeCalled();
//    }

    /**
     * @test
     */
    public function it_throws_if_the_event_identifier_is_not_set(): void
    {
        $alreadyDeliveredRequest = $this->supportedRequest('EVENT_TYPE', ['payload'], null);

        $this->expectException(BadRequestHttpException::class);
        $this->newEventAction->executeAction($alreadyDeliveredRequest);
    }

    public function wrongRequests(): array
    {
        return [
            'if the signature is missing'      => [$this->requestWithNoSignature('EVENT_TYPE', ['payload'])],
            'if the signatures does not match' => [$this->requestWithWrongSignature('EVENT_TYPE', ['payload'])],
            'if the event type is missing'     => [$this->requestWithNoEventType('EVENT_TYPE', ['payload'])],
        ];
    }

    private function supportedRequest(string $eventType, array $payload, $eventIdentifier): Request
    {
        $content = (string) json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $eventType);
        $request->headers->set('X-Hub-Signature', hash_hmac('sha1', $content, self::SECRET));
        $request->headers->set('X-GitHub-Delivery', $eventIdentifier);

        return $request;
    }

    private function requestWithNoSignature(string $eventType, array $payload): Request
    {
        $content = (string) json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $eventType);

        return $request;
    }

    private function requestWithWrongSignature(string $eventType, array $payload): Request
    {
        $content = (string) json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $eventType);
        $request->headers->set('X-Hub-Signature', 'WRONG_SIGNATURE');

        return $request;
    }

    private function requestWithNoEventType(string $eventType, array $payload): Request
    {
        $content = (string) json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-Hub-Signature', 'WRONG_SIGNATURE');

        return $request;
    }
}
