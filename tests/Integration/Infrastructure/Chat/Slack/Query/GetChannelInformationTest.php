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
use Psr\Http\Message\RequestInterface;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Chat\Slack\Query\GetChannelInformation;
use Slub\Infrastructure\Chat\Slack\Query\GetChannelInformationInterface;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetChannelInformationTest extends TestCase
{
    use ProphecyTrait;
    private MockHandler $mock;

    private GetChannelInformationInterface $getChannelInformation;

    private ObjectProphecy $slackAppInstallationRepository;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->slackAppInstallationRepository = $this->prophesize(SqlSlackAppInstallationRepository::class);
        $this->mockSlackAppInstallation();
        $this->getChannelInformation = new GetChannelInformation($client, $this->slackAppInstallationRepository->reveal());
    }

    /**
     * @test
     */
    public function it_calls_the_slack_api_to_retrieve_the_channel_information(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "channel": {"name": "general"}}'));

        $channelInformation = $this->getChannelInformation->fetch('workspace_id', '1231461');

        $generatedRequest = $this->mock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/conversations.info', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            'token=access_token&channel=1231461',
            $this->getBodyContent($generatedRequest)
        );
        $this->assertEquals('1231461', $channelInformation->channelIdentifier);
        $this->assertEquals('general', $channelInformation->channelName);
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_200(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->getChannelInformation->fetch('workspace_id', '1231461');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getChannelInformation->fetch('workspace_id', '1231461');
    }

    private function setUpGuzzleMock(): Client
    {
        $this->mock = new MockHandler([]);
        $handler = HandlerStack::create($this->mock);

        return new Client(['handler' => $handler]);
    }

    private function mockGuzzleWith(Response $response): void
    {
        $this->mock->append($response);
    }

    private function mockSlackAppInstallation(): void
    {
        $slackAppInstallation = new SlackAppInstallation();
        $slackAppInstallation->accessToken = 'access_token';
        $this->slackAppInstallationRepository->getBy('workspace_id')->willReturn($slackAppInstallation);
    }

    private function getBodyContent(RequestInterface $generatedRequest): string
    {
        return $generatedRequest->getBody()->getContents();
    }
}
