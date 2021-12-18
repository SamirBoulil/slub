<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\Query;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Chat\Slack\Query\GetBotReactionsForMessageAndUser;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotReactionsForMessageAndUserTest extends TestCase
{
    private MockHandler $httpMock;

    private GetBotReactionsForMessageAndUser $getBotReactionsForMessageAndUser;

    private ObjectProphecy $slackAppInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->slackAppInstallationRepository = $this->prophesize(SqlSlackAppInstallationRepository::class);
        $this->mockSlackAppInstallation();

        $this->getBotReactionsForMessageAndUser = new GetBotReactionsForMessageAndUser(
            $client,
            $this->slackAppInstallationRepository->reveal(),
            new NullLogger()
        );
    }

    /**
     * @test
     */
    public function it_fetches_the_bot_reactions(): void
    {
        $this->mockGuzzleWith(new Response(200, [], $this->reactions()));

        $reactions = $this->getBotReactionsForMessageAndUser->fetch(
            'workspace_id',
            'channel',
            'message_id',
            'BOT_USER_ID'
        );

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.get', $generatedRequest->getUri()->getPath());
        $this->assertEquals('channel=channel&timestamp=message_id', $generatedRequest->getUri()->getQuery());
        $this->assertEquals('Bearer access_token', $generatedRequest->getHeader('Authorization')[0]);
        $this->assertEquals(['white_check_mark', 'rocket'], $reactions);
    }

    /**
     * @test
     */
    public function it_returns_no_reaction(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "message": {}}'));

        $reactions = $this->getBotReactionsForMessageAndUser->fetch(
            'workspace_id',
            'channel',
            'message_id',
            'BOT_USER_ID'
        );

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.get', $generatedRequest->getUri()->getPath());
        $this->assertEquals('channel=channel&timestamp=message_id', $generatedRequest->getUri()->getQuery());
        $this->assertEquals('Bearer access_token', $generatedRequest->getHeader('Authorization')[0]);
        $this->assertEquals([], $reactions);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));
        $this->mockSlackAppInstallation();

        $this->expectException(\RuntimeException::class);
        $this->getBotReactionsForMessageAndUser->fetch('workspace_id', 'channel', 'message_id', 'BOT_USER_ID');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getBotReactionsForMessageAndUser->fetch('workspace_id', 'channel', 'message_id', 'BOT_USER_ID');
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

    private function mockSlackAppInstallation(): void
    {
        $slackAppInstallation = new SlackAppInstallation();
        $slackAppInstallation->accessToken = 'access_token';
        $this->slackAppInstallationRepository->getBy('workspace_id')->willReturn($slackAppInstallation);
    }
}
