<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Slub\Infrastructure\Chat\Slack\GetChannelInformation;
use Slub\Infrastructure\Chat\Slack\GetChannelInformationInterface;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class GetChannelInformationTest extends TestCase
{
    /** @var MockHandler */
    private $mock;

    /** @var GetChannelInformationInterface */
    private $getChannelInformation;

    public function setUp(): void
    {
        parent::setUp();
        $client = $this->setUpGuzzleMock();
        $this->getChannelInformation = new GetChannelInformation($client, 'xobxob-slack-token');
    }

    /**
     * @test
     */
    public function it_calls_the_slack_api_to_retrieve_the_channel_information(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true, "channel": {"name": "general"}}'));

        $channelInformation = $this->getChannelInformation->fetch('1231461');

        $generatedRequest = $this->mock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/conversations.info', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            'token=xobxob-slack-token&channel=1231461',
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
        $this->getChannelInformation->fetch('1231461');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->getChannelInformation->fetch('1231461');
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

    private function getBodyContent(RequestInterface $generatedRequest): string
    {
        return $generatedRequest->getBody()->getContents();
    }
}
