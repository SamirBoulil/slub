<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Infrastructure\Chat\Slack\GetBotUserId;
use Slub\Infrastructure\Chat\Slack\GetChannelInformationInterface;
use Slub\Infrastructure\Chat\Slack\GetPublicChannelInformation;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>

 */
class GetBotUserIdTest extends TestCase
{
    /** @var MockHandler */
    private $httpMock;

    /** @var GetBotUserId */
    private $getBotUserId;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->getBotUserId = new GetBotUserId($client, 'xobxob-slack-token');
    }

    /**
     * @test
     */
    public function it_fetches_the_slack_user_id()
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "bot": {"id": "USER_ID"}}'));

        $userId = $this->getBotUserId->fetch();

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/bots.info', $generatedRequest->getUri()->getPath());
        $this->assertEquals('token=xobxob-slack-token', $generatedRequest->getUri()->getQuery());
        $this->assertEquals('USER_ID', $userId);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200()
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->getBotUserId->fetch();
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false()
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getBotUserId->fetch();
    }

    private function setUpGuzzleMock(): Client
    {
        $this->httpMock = new MockHandler([]);
        $handler = HandlerStack::create($this->httpMock);
        $client = new Client(['handler' => $handler]);

        return $client;
    }

    private function mockGuzzleWith(Response $response): void
    {
        $this->httpMock->append($response);
    }
}
