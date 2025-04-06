<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use Slub\Infrastructure\VCS\Github\EventHandler\EventHandlerInterface;

class DummyEventHandler implements EventHandlerInterface
{
    public function supports(string $eventType, array $eventPayload): bool
    {
        return $eventType === 'dummy_event_type';
    }

    public function handle(array $request): void
    {
        // Nothing to do here
    }
}
