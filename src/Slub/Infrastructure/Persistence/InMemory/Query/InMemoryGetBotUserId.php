<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Persistence\InMemory\Query;

use Slub\Infrastructure\Chat\Slack\GetBotUserIdInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class InMemoryGetBotUserId implements GetBotUserIdInterface
{
    public function fetch(string $workspaceId): string
    {
        return 'bot_user_id';
    }
}
