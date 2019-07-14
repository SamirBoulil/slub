<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class EventHandlerRegistry
{
    private $eventHandlers = [];

    public function addEventHandler(EventHandlerInterface $eventHandler): void
    {
        $this->eventHandlers[] = $eventHandler;
    }

    public function get(string $eventType): ?EventHandlerInterface
    {
        $handlers = array_filter($this->eventHandlers,
            function (EventHandlerInterface $eventHandler) use ($eventType) {
                return $eventHandler->supports($eventType);
            }
        );

        if (empty($handlers)) {
            return null;
        }

        return current($handlers);
    }
}
