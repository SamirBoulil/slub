<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Slub\Infrastructure\Chat\Slack\Query\GetBotUserId;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetBotUserIdTest extends TestCase
{
    /** @var MockHandler */
    private $httpMock;

    /** @var GetBotUserId */
    private $getBotUserId;

    private ObjectProphecy $slackAppInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->slackAppInstallationRepository = $this->prophesize(SqlSlackAppInstallationRepository::class);
        $this->mockSlackAppInstallation();

        $this->getBotUserId = new GetBotUserId($client, $this->slackAppInstallationRepository->reveal(), new NullLogger());
    }

    /**
     * @test
     */
    public function it_fetches_the_slack_user_id(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "user_id": "USER_ID"}'));

        $userId = $this->getBotUserId->fetch('workspace_id');

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/auth.test', $generatedRequest->getUri()->getPath());
        $this->assertEquals('Bearer access_token', $generatedRequest->getHeader('Authorization')[0]);
        $this->assertEquals('USER_ID', $userId);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->getBotUserId->fetch('workspace_id');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getBotUserId->fetch('workspace_id');
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

    private function mockSlackAppInstallation(): void
    {
        $slackAppInstallation = new SlackAppInstallation();
        $slackAppInstallation->accessToken = 'access_token';
        $this->slackAppInstallationRepository->getBy('workspace_id')->willReturn($slackAppInstallation);
    }
}
