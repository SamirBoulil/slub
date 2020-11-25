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
    /**
     * @test
     */
    public function it_returns_null_when_it_does_not_find_have_the_corresponding_event_handler(): void
    {
        $expectedEventHandler = new DummyEventHandler();
        $eventHandlers = (function () use ($expectedEventHandler) {
            yield $expectedEventHandler;
        })();

        $eventHandlerRegistry = new EventHandlerRegistry($eventHandlers);

        self::assertNull($eventHandlerRegistry->get('unknown'));
        self::assertEquals($expectedEventHandler, $eventHandlerRegistry->get('dummy_event_type'));
    }
}
