<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use PHPUnit\Framework\Assert;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChatClientSpy implements ChatClient
{
    /** @var array */
    private $recordedMessages = [];

    public function replyInThread(MessageIdentifier $messageIdentifier, string $text): void
    {
        $this->recordedMessages[$messageIdentifier->stringValue()][] = $text;
    }

    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactions): void
    {
        $this->recordedMessages[$messageIdentifier->stringValue()] = array_merge($reactions,
            $this->recordedMessages[$messageIdentifier->stringValue()] ?? []);
    }

    public function assertHasBeenCalledWith(MessageIdentifier $expectedMessageIdentifier, string $expectedText): void
    {
        $reactions = $this->reactionsForMessageIdentifier($expectedMessageIdentifier);
        Assert::assertContains($expectedText, $reactions);
    }

    public function assertHasBeenCalledWithOnly(
        MessageIdentifier $expectedMessageIdentifier,
        string $expectedText
    ): void {
        $reactions = $this->reactionsForMessageIdentifier($expectedMessageIdentifier);
        Assert::assertEquals([$expectedText], $reactions);
    }

    public function reset(): void
    {
        $this->recordedMessages = [];
    }

    private function reactionsForMessageIdentifier(MessageIdentifier $expectedMessageIdentifier): array
    {
        $key = $expectedMessageIdentifier->stringValue();
        Assert::assertArrayHasKey(
            $key,
            $this->recordedMessages,
            sprintf('Expected to have a reply to message ID "%s"', $expectedMessageIdentifier->stringValue())
        );
        $reactions = $this->recordedMessages[$key];
        Assert::assertNotEmpty($reactions);

        return $reactions;
    }
}
