<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use PHPUnit\Framework\Assert;
use Slub\Application\Common\ChatClient;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
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

    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text)
    {
        $this->recordedMessages[$channelIdentifier->stringValue()][] = $text;
    }

    public function assertReaction(MessageIdentifier $expectedMessageIdentifier, string $expectedText): void
    {
        $reactions = $this->reactionsForIdentifier($expectedMessageIdentifier->stringValue());
        Assert::assertContains($expectedText, $reactions);
    }

    public function assertHasBeenCalledWithChannelIdentifierAndMessage(ChannelIdentifier $expectedChannelIdentifier, string $expectedText): void
    {
        $actualText = $this->reactionsForIdentifier($expectedChannelIdentifier->stringValue());
        Assert::assertContains($expectedText, $actualText);
    }

    public function assertOnlyReaction(
        MessageIdentifier $expectedMessageIdentifier,
        string $expectedText
    ): void {
        $reactions = $this->reactionsForIdentifier($expectedMessageIdentifier->stringValue());
        Assert::assertEquals([$expectedText], $reactions);
    }

    public function assertRepliedWithOneOf(array $expectedMessages)
    {
        $isFound = false;
        foreach ($this->recordedMessages as $actualMessages) {
            foreach ($actualMessages as $actualMessage) {
                if (in_array($actualMessage, $expectedMessages)) {
                    $isFound = true;
                }
            }
        }

        Assert::assertTrue($isFound, 'Did not find any of the messages');
    }

    public function reset(): void
    {
        $this->recordedMessages = [];
    }

    public function assertEmpty(): void
    {
        Assert::assertEmpty($this->recordedMessages);
    }

    private function reactionsForIdentifier(string $expectedIdentifier): array
    {
        Assert::assertArrayHasKey(
            $expectedIdentifier,
            $this->recordedMessages,
            sprintf('Expected to have a reply/message to message ID "%s"', $expectedIdentifier)
        );
        $reactions = $this->recordedMessages[$expectedIdentifier];
        Assert::assertNotEmpty($reactions);

        return $reactions;
    }
}
