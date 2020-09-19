<?php
declare(strict_types=1);

namespace Slub\Application\Common;

use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;

interface ChatClient
{
    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void;
    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactions): void;
    public function publishInChannel(WorkspaceIdentifier $channelIdentifier, string $text);
}
