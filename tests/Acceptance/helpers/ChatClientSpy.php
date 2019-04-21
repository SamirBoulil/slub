<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use PHPUnit\Framework\Assert;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class ChatClientSpy implements ChatClient
{
    /** @var string[][] */
    private $recordedMessages = [];

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $this->recordedMessages[$messageIdentifier->stringValue()][] = $text;
    }

    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactions): void
    {
        $this->recordedMessages[$messageIdentifier->stringValue()] = array_merge($reactions, $this->recordedMessages[$messageIdentifier->stringValue()] ?? []);
    }

    public function assertHasBeenCalledWith(MessageIdentifier $expectedMessageIdentifier, string $expectedText): void
    {
        $key = $expectedMessageIdentifier->stringValue();
        Assert::assertArrayHasKey(
            $key,
            $this->recordedMessages,
            sprintf('Expected to have a reply to message ID "%s"', $expectedMessageIdentifier->stringValue())
        );
        Assert::assertNotEmpty($this->recordedMessages[$key]);
        Assert::assertContains($expectedText, $this->recordedMessages[$key]);
    }

    public function reset(): void
    {
        $this->recordedMessages = [];
    }
}
