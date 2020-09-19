<?php
declare(strict_types=1);

namespace Slub\Application\Common;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;

interface ChatClient
{
    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void;
    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactions): void;
    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text);
}
