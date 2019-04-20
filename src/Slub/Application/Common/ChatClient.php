<?php
declare(strict_types=1);

namespace Slub\Application\Common;

use Slub\Domain\Entity\PR\MessageIdentifier;

interface ChatClient
{
    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void;
    public function reactToMessageWith(MessageIdentifier $messageIdentifier, string $text): void;
}
