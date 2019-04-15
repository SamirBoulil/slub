<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use Slub\Application\NotifySquad\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ChatClientSpy implements ChatClient
{
    /** @var MessageIdentifier */
    private $actualMessageIdentifier;

    /** @var string */
    private $actualText;

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $this->actualMessageIdentifier = $messageIdentifier;
        $this->actualText = $text;
    }

    public function reactToMessageWith(MessageIdentifier $messageIdentifier, string $text): void
    {
        $this->actualMessageIdentifier = $messageIdentifier;
        $this->actualText = $text;
    }

    public function assertHasBeenCalledWith(MessageIdentifier $expectedMessageIdentifier, string $expectedText): void
    {
        if (
            null === $this->actualMessageIdentifier ||
            null === $this->actualText ||
            (!$expectedMessageIdentifier->equals($this->actualMessageIdentifier) || $expectedText !== $this->actualText)
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Chat client has not replied on message "%s" the text "%s"',
                    $expectedMessageIdentifier->stringValue(),
                    $expectedText
                )
            );
        }
    }
}
