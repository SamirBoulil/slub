<?php

declare(strict_types=1);

namespace Slub\Infrastructure\Chat\Slack\Common;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class BotNotInChannelException extends \LogicException
{
    protected $message = 'Bot is not in the channel.';
}
