<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class EventHandlerRegistry
{
    /** @var EventHandlerInterface[] */
    private array $eventHandlers = [];

    public function __construct(iterable $eventHandlers)
    {
        if ($eventHandlers instanceof \Traversable) {
            $this->eventHandlers = iterator_to_array($eventHandlers);
        }
    }

    public function get(string $eventType): ?EventHandlerInterface
    {
        $handlers = array_filter($this->eventHandlers,
            fn (EventHandlerInterface $eventHandler) => $eventHandler->supports($eventType)
        );

        if (empty($handlers)) {
            return null;
        }

        return current($handlers);
    }
}
