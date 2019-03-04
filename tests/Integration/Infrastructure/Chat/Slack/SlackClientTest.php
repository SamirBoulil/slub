<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Infrastructure\Chat\Slack\SlackClient;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class SlackClientTest extends KernelTestCase
{
    /** @var MockHandler */
    private $mock;

    /** @var SlackClient */
    private $slackClient;

    public function setUp(): void
    {
        parent::setUp();

        // Could have an GuzzleTestCase with mock & client as properties
        $client = $this->setUpGuzzleMock();
        $this->slackClient = new SlackClient($client, 'xobxob-slack-token');
    }

    /**
     * @test
     */
    public function it_replies_in_thread(): void
    {
        $this->mockGuzzleWith(new Response(200, [], ''));

        $this->slackClient->replyInThread(MessageIdentifier::fromString('channel@message'), 'hello world');

        $generatedRequest = $this->mock->getLastRequest();
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
        $this->mock = new MockHandler([]);
        $handler = HandlerStack::create($this->mock);
        $client = new Client(['handler' => $handler]);

        return $client;
    }

    private function getBodyContent($generatedRequest): array
    {
        return json_decode($generatedRequest->getBody()->getContents(), true);
    }

    private function mockGuzzleWith(Response $response): void
    {
        $this->mock->append($response);
    }
}
