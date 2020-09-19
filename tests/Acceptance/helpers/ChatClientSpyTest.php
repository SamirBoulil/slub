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
    /** @var ChatClientSpy */
    private $slackClientSpy;

    public function setUp()
    {
        parent::setUp();

        $this->slackClientSpy = new ChatClientSpy();
    }

    /**
     * @test
     */
    public function it_asserts_that_it_has_been_called_with_the_expected_arguments()
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
     * @expectedException \RuntimeException
     */
    public function it_throws_if_a_reply_in_a_thread_was_made_and_it_asserts_empty()
    {
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->replyInThread(MessageIdentifier::fromString('general@12345'), 'hello');

        $this->expectException(AssertionFailedError::class);
        $slackClientSpy->assertEmpty();
    }

    /**
     * @test
     */
    public function it_throws_if_a_reaction_to_a_message_was_made_and_it_asserts_empty()
    {
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->setReactionsToMessageWith(MessageIdentifier::fromString('general@12345'), ['reaction']);

        $this->expectException(AssertionFailedError::class);
        $slackClientSpy->assertEmpty();
    }

    /**
     * @test
     */
    public function it_throws_if_message_was_published_in_a_channel_and_it_asserts_empty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $slackClientSpy = new ChatClientSpy();
        $slackClientSpy->publishInChannel(ChannelIdentifier::fromString('general@12345'), 'text');

        $this->expectException(AssertionFailedError::class);
        $slackClientSpy->assertEmpty();
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_with_the_expected_message_identifier()
    {
        $text = 'hello';
        $this->slackClientSpy->replyInThread(MessageIdentifier::fromString('general@12345'), $text);

        $this->expectException(AssertionFailedError::class);
        $this->slackClientSpy->assertReaction(MessageIdentifier::fromString('another_one'), $text);
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_with_the_expected_text()
    {
        $messageIdentifier = MessageIdentifier::fromString('general@12345');
        $this->slackClientSpy->replyInThread($messageIdentifier, 'hello');

        $this->expectException(AssertionFailedError::class);
        $this->slackClientSpy->assertReaction($messageIdentifier, 'another_text');
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_prior_to_asserting()
    {
        $this->expectException(AssertionFailedError::class);
        $this->slackClientSpy->assertReaction(
            MessageIdentifier::fromString('general@12345'),
            'another_text'
        );
    }
}
