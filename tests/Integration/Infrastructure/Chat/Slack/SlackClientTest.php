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
    protected $mock;

    /** @var SlackClient */
    protected $slackClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpGuzzleMock();
    }

    /**
     * @test
     */
    public function it_replies_in_thread(): void
    {
        $this->slackClient->replyInThread(MessageIdentifier::fromString('channel@message'), 'hello world');

        $generatedRequest = $this->mock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/chat.postMessage', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel'   => 'channel',
                'thread_ts' => 'message',
                'text'      => 'hello world',
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    private function setUpGuzzleMock(): void
    {
        $this->mock = new MockHandler([new Response(200, [], '')]);
        $handler = HandlerStack::create($this->mock);
        $client = new Client(['handler' => $handler]);
        $this->slackClient = new SlackClient($client);
    }

    private function getBodyContent($generatedRequest): array
    {
        return json_decode($generatedRequest->getBody()->getContents(), true);
    }
}
