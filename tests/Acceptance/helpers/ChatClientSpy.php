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

    public function setReactionsToMessageWith(MessageIdentifier $messageIdentifier, array $reactionsToSet): void
    {
        $this->recordedMessages[$messageIdentifier->stringValue()] = array_merge($reactionsToSet,
                                                                                 $this->recordedMessages[$messageIdentifier->stringValue()] ?? []);
    }

    public function publishInChannel(ChannelIdentifier $channelIdentifier, string $text): void
    {
        $this->recordedMessages[$channelIdentifier->stringValue()][] = $text;
    }

    public function publishMessageWithBlocksInChannel(ChannelIdentifier $channelIdentifier, array $blocks): string
    {
        $this->recordedMessages[$channelIdentifier->stringValue()][] = $blocks;

        return 'published_message_identifier';
    }

    public function assertPublishMessageWithBlocksInChannelContains(string $channelIdentifier, string $textMessage): void
    {
        Assert::assertIsArray($this->recordedMessages[$channelIdentifier]);
        Assert::assertContains($this->recordedMessages[$channelIdentifier][0]['text']['text'] ?? 'Block does not exist!', $textMessage);
    }

    public function answerWithEphemeralMessage(string $url, string $text): void
    {
        $this->recordedMessages[$url] = $text;
    }

    public function assertEphemeralMessageContains(string $url, string $text): void
    {
        Assert::assertArrayHasKey($url, $this->recordedMessages);
        Assert::assertStringContainsString($this->recordedMessages[$url], $text);
    }

    public function assertReaction(MessageIdentifier $expectedMessageIdentifier, string $expectedText): void
    {
        $reactions = $this->reactionsForIdentifier($expectedMessageIdentifier->stringValue());
        Assert::assertContains($expectedText, $reactions);
    }

    public function assertOnlyReaction(
        MessageIdentifier $expectedMessageIdentifier,
        string $expectedText
    ): void {
        $reactions = $this->reactionsForIdentifier($expectedMessageIdentifier->stringValue());
        Assert::assertEquals([$expectedText], $reactions);
    }

    public function assertHasBeenCalledWithChannelIdentifierAndMessage(ChannelIdentifier $expectedChannelIdentifier, string $expectedText): void
    {
        $actualText = $this->reactionsForIdentifier($expectedChannelIdentifier->stringValue());
        Assert::assertContains($expectedText, $actualText);
    }

    public function assertRepliedWithOneOf(array $expectedMessages): void
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

    public function explainPRURLCannotBeParsed(string $url, string $usage): void
    {
        $this->answerWithEphemeralMessage($url, $usage);
    }

    public function explainAppNotInstalled(string $url, string $usage): void
    {
        $this->answerWithEphemeralMessage($url, $usage);
    }

    public function explainSomethingWentWrong(string $url, string $usage, string $action): void
    {
        $this->answerWithEphemeralMessage($url, $usage);
    }
}
