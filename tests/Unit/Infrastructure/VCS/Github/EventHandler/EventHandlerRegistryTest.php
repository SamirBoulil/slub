<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerInterface;
use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerRegistry;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class EventHandlerRegistryTest extends TestCase
{
    /** @var EventHandlerRegistry */
    private $eventHandlerRegistry;

    public function setUp()
    {
        $this->eventHandlerRegistry = new EventHandlerRegistry();
    }

    /**
     * @test
     */
    public function it_returns_null_when_it_does_not_find_have_the_corresponding_event_handler()
    {
        self::assertNull($this->eventHandlerRegistry->get('unknown'));
    }

    /**
     * @test
     */
    public function it_returns_the_corresponding_event_handler()
    {
        $expectedEventHandler = new DummyEventHandler();

        $this->eventHandlerRegistry->addEventHandler($expectedEventHandler);

        self::assertEquals($expectedEventHandler, $this->eventHandlerRegistry->get('dummy_event_type'));
    }
}
