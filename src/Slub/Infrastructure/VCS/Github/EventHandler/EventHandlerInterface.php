<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
*/
interface EventHandlerInterface
{
    public function supports(string $eventType): bool;
    public function handle(array $request): void;
}
