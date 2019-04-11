<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Symfony\Component\HttpFoundation\Request;

/**
 * {description}
 *
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface EventHandlerInterface
{
    public function supports(string $eventType): bool;
    public function handle(Request $request): void;
}
