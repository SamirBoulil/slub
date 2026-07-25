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
use Slub\Infrastructure\Chat\Slack\Query\GetMessagePermalink;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetMessagePermalinkTest extends TestCase
{
    use ProphecyTrait;

    private MockHandler $httpMock;

    private GetMessagePermalink $getMessagePermalink;

    private ObjectProphecy $slackAppInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->slackAppInstallationRepository = $this->prophesize(SqlSlackAppInstallationRepository::class);
        $this->mockSlackAppInstallation();

        $this->getMessagePermalink = new GetMessagePermalink(
            $client,
            $this->slackAppInstallationRepository->reveal()
        );
    }

    /**
     * @test
     */
    public function it_fetches_the_permalink_of_a_message(): void
    {
        $this->mockGuzzleWith(
            new Response(
                200,
                [],
                '{"ok": true, "channel": "C1H9RESGL", "permalink": "https://my-workspace.slack.com/archives/C1H9RESGL/p135854651500008"}'
            )
        );

        $permalink = $this->getMessagePermalink->fetch('workspace_id', 'channel', 'message_id');

        $generatedRequest = $this->httpMock->getLastRequest();
        $this->assertEquals('GET', $generatedRequest->getMethod());
        $this->assertEquals('/api/chat.getPermalink', $generatedRequest->getUri()->getPath());
        $this->assertEquals('channel=channel&message_ts=message_id', $generatedRequest->getUri()->getQuery());
        $this->assertEquals('Bearer access_token', $generatedRequest->getHeader('Authorization')[0]);
        $this->assertEquals('https://my-workspace.slack.com/archives/C1H9RESGL/p135854651500008', $permalink);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->getMessagePermalink->fetch('workspace_id', 'channel', 'message_id');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false, "error": "message_not_found"}'));

        $this->expectException(\RuntimeException::class);
        $this->getMessagePermalink->fetch('workspace_id', 'channel', 'message_id');
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

    private function mockSlackAppInstallation(): void
    {
        $slackAppInstallation = new SlackAppInstallation();
        $slackAppInstallation->accessToken = 'access_token';
        $this->slackAppInstallationRepository->getBy('workspace_id')->willReturn($slackAppInstallation);
    }
}
