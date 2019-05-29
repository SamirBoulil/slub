<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerInterface;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerRegistry;
use Slub\Infrastructure\VCS\Github\NewEventAction;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class NewEventActionTest extends TestCase
{
    private const SECRET = 'SECRET';

    /**
     * @var NewEventAction
     * @sut
     */
    private $newEventAction;

    /** @var \Prophecy\Prophecy\ObjectProphecy|EventHandlerRegistry */
    private $eventHandlerRegistry;

    public function setUp()
    {
        $this->eventHandlerRegistry = $this->prophesize(EventHandlerRegistry::class);
        $this->newEventAction = new NewEventAction($this->eventHandlerRegistry->reveal(), self::SECRET);
    }

    /**
     * @test
     */
    public function it_successfully_processes_supported_events()
    {
        $eventType = 'EVENT_TYPE';
        $eventPayload = ['payload'];
        $supportedRequest = $this->supportedRequest($eventType, $eventPayload);
        $eventHandler = $this->prophesize(EventHandlerInterface::class);
        $eventHandler->handle($eventPayload)->shouldBeCalled();
        $this->eventHandlerRegistry->get($eventType)->willReturn($eventHandler->reveal());

        $this->newEventAction->executeAction($supportedRequest);
    }

    /**
     * @test
     * @dataProvider wrongRequests
     */
    public function it_throws(Request $wrongRequest)
    {
        $this->expectException(\Exception::class);
        $this->eventHandlerRegistry->get(Argument::any())->willReturn(null);

        $this->newEventAction->executeAction($wrongRequest);
    }

    public function wrongRequests()
    {
        return [
            'if the signature is missing'      => [$this->requestWithNoSignature('EVENT_TYPE', ['payload'])],
            'if the signatures does not match' => [$this->requestWithWrongSignature('EVENT_TYPE', ['payload'])],
            'if the event type is missing'     => [$this->requestWithNoEventType('EVENT_TYPE', ['payload'])],
            'if the event type is unsupported'     => [$this->supportedRequest('EVENT_TYPE', ['payload'])],
        ];
    }

    private function supportedRequest(string $eventType, array $payload): Request
    {
        $content = (string) json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $eventType);
        $request->headers->set('X-Hub-Signature', hash_hmac('sha1', $content, self::SECRET));

        return $request;
    }

    private function requestWithNoSignature(string $eventType, array $payload)
    {
        $content = json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $eventType);

        return $request;
    }

    private function requestWithWrongSignature(string $eventType, array $payload): Request
    {
        $content = json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-GitHub-Event', $eventType);
        $request->headers->set('X-Hub-Signature', 'WRONG_SIGNATURE');

        return $request;
    }

    private function requestWithNoEventType(string $eventType, array $payload): Request
    {
        $content = json_encode($payload);
        $request = new Request([], [], [], [], [], [], $content);
        $request->headers->set('X-Hub-Signature', 'WRONG_SIGNATURE');

        return $request;
    }
}
