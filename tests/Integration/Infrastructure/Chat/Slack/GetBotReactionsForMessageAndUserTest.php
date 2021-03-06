<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Slub\Infrastructure\Chat\Slack\GetBotReactionsForMessageAndUser;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotReactionsForMessageAndUserTest extends TestCase
{
    /** @var MockHandler */
    private $httpMock;

    /** @var GetBotReactionsForMessageAndUser */
    private $getBotReactionsForMessageAndUser;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->getBotReactionsForMessageAndUser = new GetBotReactionsForMessageAndUser(
            $client,
            'xobxob-slack-token'
        );
    }

    /**
     * @test
     */
    public function it_fetches_the_bot_reactions(): void
    {
        $this->mockGuzzleWith(new Response(200, [], $this->reactions()));

        $reactions = $this->getBotReactionsForMessageAndUser->fetch('channel', 'message_id', 'BOT_USER_ID');

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.get', $generatedRequest->getUri()->getPath());
        $this->assertEquals('token=xobxob-slack-token&channel=channel&timestamp=message_id', $generatedRequest->getUri()->getQuery());
        $this->assertEquals(['white_check_mark', 'rocket'], $reactions);
    }

    /**
     * @test
     */
    public function it_returns_no_reaction(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "message": {}}'));

        $reactions = $this->getBotReactionsForMessageAndUser->fetch('channel', 'message_id', 'BOT_USER_ID');

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.get', $generatedRequest->getUri()->getPath());
        $this->assertEquals('token=xobxob-slack-token&channel=channel&timestamp=message_id', $generatedRequest->getUri()->getQuery());
        $this->assertEquals([], $reactions);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->getBotReactionsForMessageAndUser->fetch('channel', 'message_id', 'BOT_USER_ID');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getBotReactionsForMessageAndUser->fetch('channel', 'message_id', 'BOT_USER_ID');
    }

    private function setUpGuzzleMock(): Client
    {
        $this->httpMock = new MockHandler([]);
        $handler = HandlerStack::create($this->httpMock);

        return new Client(['handler' => $handler]);
    }

    private function mockGuzzleWith(Response $response): void
    {
        $this->httpMock->append($response);
    }

    private function reactions(): string
    {
        return <<<json
{
    "message": {
        "reactions": [
            {
                "count": 1,
                "name": "white_check_mark",
                "users": [
                    "other_user",
                    "BOT_USER_ID"
                ]
            },
            {
                "count": 1,
                "name": "rocket",
                "users": [
                    "other_user",
                    "BOT_USER_ID"
                ]
            },
            {
                "count": 1,
                "name": "+1",
                "users": [
                    "other_user"
                ]
            }
        ]
    },
    "ok": true
}
json;
    }
}
