<?php

declare(strict_types=1);

namespace Tests\Acceptance\helpers;

use PHPUnit\Framework\TestCase;
use Slub\Domain\Entity\PR\MessageIdentifier;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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

        $this->slackClientSpy->assertHasBeenCalledWith($messageIdentifier, $text);
        $this->assertTrue(true, 'No exception was thrown');
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_with_the_expected_message_identifier()
    {
        $text = 'hello';
        $this->slackClientSpy->replyInThread(MessageIdentifier::fromString('general@12345'), $text);

        $this->expectException(\InvalidArgumentException::class);
        $this->slackClientSpy->assertHasBeenCalledWith(MessageIdentifier::fromString('another_one'), $text);
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_with_the_expected_text()
    {
        $messageIdentifier = MessageIdentifier::fromString('general@12345');
        $this->slackClientSpy->replyInThread($messageIdentifier, 'hello');

        $this->expectException(\InvalidArgumentException::class);
        $this->slackClientSpy->assertHasBeenCalledWith($messageIdentifier, 'another_text');
    }

    /**
     * @test
     */
    public function it_throws_if_it_has_not_been_called_prior_to_asserting()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->slackClientSpy->assertHasBeenCalledWith(
            MessageIdentifier::fromString('general@12345'),
            'another_text'
        );
    }
}
