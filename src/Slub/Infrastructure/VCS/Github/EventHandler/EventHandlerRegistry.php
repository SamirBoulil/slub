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

    /**
     * @return array<EventHandlerInterface>
     */
    public function get(string $eventType): array
    {
        $handlers = array_filter(
            $this->eventHandlers,
            static fn (EventHandlerInterface $eventHandler): bool => $eventHandler->supports($eventType)
        );

        if (empty($handlers)) {
            return [];
        }

        return $handlers;
    }
}
