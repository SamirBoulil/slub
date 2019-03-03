<?php
declare(strict_types=1);

namespace Slub\Application\NotifySquad;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;

interface ChatClient
{
    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void;
}
