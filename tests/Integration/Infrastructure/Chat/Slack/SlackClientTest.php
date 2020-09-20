<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Infrastructure\Chat\Slack\GetBotReactionsForMessageAndUser;
use Slub\Infrastructure\Chat\Slack\GetBotUserId;
use Slub\Infrastructure\Chat\Slack\SlackClient;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlackClientTest extends KernelTestCase
{
    /** @var ObjectProphecy */
    private $getBotUserId;

    /** @var ObjectProphecy */
    private $getBotReactionsForMessageAndUser;

    /** @var MockHandler */
    private $httpClientMock;

    /** @var SlackClient */
    private $slackClient;

    public function setUp(): void
    {
        parent::setUp();

        // Could have an GuzzleTestCase with mock & client as properties
        $client = $this->setUpGuzzleMock();
        $this->getBotUserId = $this->prophesize(GetBotUserId::class);
        $this->getBotReactionsForMessageAndUser = $this->prophesize(GetBotReactionsForMessageAndUser::class);

        $this->slackClient = new SlackClient(
            $this->getBotUserId->reveal(), $this->getBotReactionsForMessageAndUser->reveal(), $client, $this->prophesize(LoggerInterface::class)->reveal(), 'xobxob-slack-token', 'USER_ID' // TODO: to remove
        );
    }

    /**
     * @test
     */
    public function it_replies_in_thread(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));

        $this->slackClient->replyInThread(MessageIdentifier::fromString('channel@message'), 'hello world');

        $generatedRequest = $this->httpClientMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/chat.postMessage', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel'   => 'channel',
                'thread_ts' => 'message',
                'text'      => 'hello world',
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_adds_reactions_to_messages(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
//        $this->getBotUserId->fetch()->shouldBeCalled()->willReturn('USER_ID');
        $this->getBotReactionsForMessageAndUser->fetch('channel', 'message', 'USER_ID')
            ->shouldBeCalled()
            ->willReturn(['ok_hand']);

        $this->slackClient->setReactionsToMessageWith(MessageIdentifier::fromString('channel@message'), ['ok_hand', 'rocket']);

        $generatedRequest = $this->httpClientMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.add', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel'   => 'channel',
                'timestamp' => 'message',
                'name'      => 'rocket',
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_removes_reactions_from_the_messages(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
//        $this->getBotUserId->fetch()->shouldBeCalled()->willReturn('USER_ID');
        $this->getBotReactionsForMessageAndUser->fetch('channel', 'message', 'USER_ID')
            ->shouldBeCalled()
            ->willReturn(['ok_hand', 'one', 'red_ci']);

        $this->slackClient->setReactionsToMessageWith(
            MessageIdentifier::fromString('channel@message'),
            ['ok_hand', 'one']
        );

        $generatedRequest = $this->httpClientMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.remove', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel'   => 'channel',
                'timestamp' => 'message',
                'name'      => 'red_ci',
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_does_not_update_the_reactions(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
//        $this->getBotUserId->fetch()->shouldBeCalled()->willReturn('USER_ID');
        $this->getBotReactionsForMessageAndUser->fetch('channel', 'message', 'USER_ID')
            ->shouldBeCalled()
            ->willReturn(['ok_hand', 'one', 'red_ci']);

        $this->slackClient->setReactionsToMessageWith(
            MessageIdentifier::fromString('channel@message'),
            ['ok_hand', 'one', 'red_ci']
        );
        $this->assertNull($this->httpClientMock->getLastRequest());
    }

    /**
     * @test
     */
    public function it_publishes_a_message_in_a_channel(): void
    {
        $message = 'a message';
        $channel = 'channel';

        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
        $this->slackClient->publishInChannel(ChannelIdentifier::fromString($channel), $message);

        $generatedRequest = $this->httpClientMock->getLastRequest();
        self::assertEquals('POST', $generatedRequest->getMethod());
        self::assertEquals('/api/chat.postMessage', $generatedRequest->getUri()->getPath());
        self::assertEquals(
            ['channel' => $channel, 'text' => $message],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_ok(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->slackClient->replyInThread(MessageIdentifier::fromString('channel@message'), 'hello world');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->slackClient->replyInThread(MessageIdentifier::fromString('channel@message'), 'hello world');
    }

    private function setUpGuzzleMock(): Client
    {
        $this->httpClientMock = new MockHandler([]);
        $handler = HandlerStack::create($this->httpClientMock);
        $client = new Client(['handler' => $handler]);

        return $client;
    }

    private function getBodyContent($generatedRequest): array
    {
        return json_decode($generatedRequest->getBody()->getContents(), true);
    }

    private function mockGuzzleWith(Response $response): void
    {
        $this->httpClientMock->append($response);
    }
}
