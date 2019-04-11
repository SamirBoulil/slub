<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class EventHandlerRegistry
{
    private $eventHandlers;

    public function __construct(EventHandlerInterface $newReviewEvent)
    {
        $this->eventHandlers = [$newReviewEvent];
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
