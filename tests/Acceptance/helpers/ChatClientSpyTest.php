<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class ChatClientSpyTest extends TestCase
{
    private ChatClientSpy $slackClientSpy;

    public function setUp(): void
    {
        parent::setUp();

        $this->slackClientSpy = new ChatClientSpy();
    }

    /**
     * @test
     */
    public function it_asserts_that_it_has_been_called_with_the_expected_arguments(): void
    {
        $messageIdentifier = MessageIdentifier::fromString('general@12345');
        $text = 'hello';

        $this->slackClientSpy->replyInThread($messageIdentifier, $text);

        $this->slackClientSpy->assertReaction($messageIdentifier, $text);
        $this->assertTrue(true, 'No exception was thrown');
    }

    /**
     * @test
     */
    public function it_asserts_there_was_no_message_sent(): void
    {
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->assertEmpty();
        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function it_throws_if_a_reply_in_a_thread_was_made_and_it_asserts_empty(): void
    {
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->replyInThread(
            MessageIdentifier::fromString('general@12345'),
            'hello'
        );

        $this->expectException(AssertionFailedError::class);
        $slackClientSpy->assertEmpty();
    }

    /**
     * @test
     */
    public function it_throws_if_a_reaction_to_a_message_was_made_and_it_asserts_empty(): void
    {
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->setReactionsToMessageWith(
            MessageIdentifier::fromString('general@12345'),
            ['reaction']
        );

        $this->expectException(AssertionFailedError::class);
        $slackClientSpy->assertEmpty();
    }

    /**
     * @test
     */
    public function it_throws_if_message_was_published_in_a_channel_and_it_asserts_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->publishInChannel(
            ChannelIdentifier::fromString('general@12345'),
            'text'
        );

        $this->expectException(AssertionFailedError::class);
        $slackClientSpy->assertEmpty();
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_with_the_expected_message_identifier(): void
    {
        $text = 'hello';
        $this->slackClientSpy->replyInThread(
            MessageIdentifier::fromString('general@12345'),
            $text
        );

        $this->expectException(AssertionFailedError::class);
        $this->slackClientSpy->assertReaction(MessageIdentifier::fromString('another_one'), $text);
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_with_the_expected_text(): void
    {
        $messageIdentifier = MessageIdentifier::fromString('general@12345');
        $this->slackClientSpy->replyInThread(
            $messageIdentifier,
            'hello'
        );

        $this->expectException(AssertionFailedError::class);
        $this->slackClientSpy->assertReaction($messageIdentifier, 'another_text');
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_prior_to_asserting(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->slackClientSpy->assertReaction(
            MessageIdentifier::fromString('general@12345'),
            'another_text'
        );
    }

    /**
     * @test
     */
    public function it_returns_the_stubbed_reaction_count_and_zero_by_default(): void
    {
        $messageIdentifier = MessageIdentifier::fromString('general@12345');

        self::assertEquals(0, $this->slackClientSpy->getReactionCountForMessage($messageIdentifier, 'white_check_mark'));

        $this->slackClientSpy->stubReactionCount('general@12345', 'white_check_mark', 3);

        self::assertEquals(3, $this->slackClientSpy->getReactionCountForMessage($messageIdentifier, 'white_check_mark'));
        self::assertEquals(0, $this->slackClientSpy->getReactionCountForMessage($messageIdentifier, 'rocket'));
    }

    /**
     * @test
     */
    public function it_throws_the_stubbed_exception_when_fetching_the_reaction_count(): void
    {
        $this->slackClientSpy->stubReactionCount('general@12345', 'white_check_mark', new \RuntimeException('Slack API failure'));

        $this->expectException(\RuntimeException::class);
        $this->slackClientSpy->getReactionCountForMessage(
            MessageIdentifier::fromString('general@12345'),
            'white_check_mark'
        );
    }

    /**
     * @test
     */
    public function it_returns_a_deterministic_permalink(): void
    {
        self::assertEquals(
            'https://slack.example.com/permalink/general@12345',
            $this->slackClientSpy->getMessagePermalink(MessageIdentifier::fromString('general@12345'))
        );
    }
}
