<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\Query;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Chat\Slack\Query\GetReactionsForMessage;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetReactionsForMessageTest extends TestCase
{
    use ProphecyTrait;

    private MockHandler $httpMock;

    private GetReactionsForMessage $getReactionsForMessage;

    private ObjectProphecy $slackAppInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->slackAppInstallationRepository = $this->prophesize(SqlSlackAppInstallationRepository::class);
        $this->mockSlackAppInstallation();

        $this->getReactionsForMessage = new GetReactionsForMessage(
            $client,
            $this->slackAppInstallationRepository->reveal()
        );
    }

    /**
     * @test
     */
    public function it_fetches_the_reactions_of_a_message(): void
    {
        $this->mockGuzzleWith(new Response(200, [], $this->reactions()));

        $reactions = $this->getReactionsForMessage->fetch('workspace_id', 'channel', 'message_id');

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.get', $generatedRequest->getUri()->getPath());
        $this->assertEquals('channel=channel&timestamp=message_id', $generatedRequest->getUri()->getQuery());
        $this->assertEquals('Bearer access_token', $generatedRequest->getHeader('Authorization')[0]);
        $this->assertEquals(
            [
                ['count' => 2, 'name' => 'white_check_mark', 'users' => ['user_1', 'user_2']],
                ['count' => 1, 'name' => 'rocket', 'users' => ['user_1']],
            ],
            $reactions
        );
    }

    /**
     * @test
     */
    public function it_returns_no_reactions_when_the_message_has_none(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "message": {}}'));

        $reactions = $this->getReactionsForMessage->fetch('workspace_id', 'channel', 'message_id');

        $this->assertEquals([], $reactions);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->getReactionsForMessage->fetch('workspace_id', 'channel', 'message_id');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getReactionsForMessage->fetch('workspace_id', 'channel', 'message_id');
    }

    private function setUpGuzzleMock(): Client
    {
        $this->httpMock = new MockHandler();
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
                "count": 2,
                "name": "white_check_mark",
                "users": [
                    "user_1",
                    "user_2"
                ]
            },
            {
                "count": 1,
                "name": "rocket",
                "users": [
                    "user_1"
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
