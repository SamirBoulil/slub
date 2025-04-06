<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
*/
interface EventHandlerInterface
{
    public function supports(string $eventType, array $eventPayload): bool;
    public function handle(array $request): void;
}
