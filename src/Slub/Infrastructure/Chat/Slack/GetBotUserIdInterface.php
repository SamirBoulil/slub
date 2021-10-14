<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
interface GetBotUserIdInterface
{
    public function fetch(string $workspaceId): string;
}
