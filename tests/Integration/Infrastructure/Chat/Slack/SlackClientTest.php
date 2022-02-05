<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Infrastructure\Chat\Slack\AppInstallation\SlackAppInstallation;
use Slub\Infrastructure\Chat\Slack\Common\MessageIdentifierHelper;
use Slub\Infrastructure\Chat\Slack\Query\GetBotReactionsForMessageAndUser;
use Slub\Infrastructure\Chat\Slack\Query\GetBotUserId;
use Slub\Infrastructure\Chat\Slack\SlackClient;
use Slub\Infrastructure\Persistence\Sql\Repository\SqlSlackAppInstallationRepository;
use Tests\Integration\Infrastructure\KernelTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class SlackClientTest extends KernelTestCase
{
    /** @var ObjectProphecy */
    private $getBotUserId;

    /** @var ObjectProphecy */
    private $getBotReactionsForMessageAndUser;

    /** @var MockHandler */
    private $httpClientMock;

    /** @var SlackClient */
    private $slackClient;

    public function setUp(): void
    {
        parent::setUp();

        // Could have an GuzzleTestCase with mock & client as properties
        $client = $this->setUpGuzzleMock();
        $this->getBotUserId = $this->prophesize(GetBotUserId::class);
        $this->getBotReactionsForMessageAndUser = $this->prophesize(GetBotReactionsForMessageAndUser::class);

        $slackAppInstallationRepository = $this->prophesize(SqlSlackAppInstallationRepository::class);
        $this->mockSlackAppInstallation($slackAppInstallationRepository);

        $this->slackClient = new SlackClient(
            $this->getBotUserId->reveal(),
            $this->getBotReactionsForMessageAndUser->reveal(),
            $client,
            $this->prophesize(LoggerInterface::class)->reveal(),
            $slackAppInstallationRepository->reveal()
        );
    }

    /**
     * @test
     */
    public function it_replies_in_thread(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));

        $this->slackClient->replyInThread(MessageIdentifier::fromString('workspace@channel@message'), 'hello world');

        $generatedRequest = $this->httpClientMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/chat.postMessage', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel' => 'channel',
                'thread_ts' => 'message',
                'text' => 'hello world',
                'unfurl_links' => false,
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_adds_reactions_to_messages(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
        $this->getBotUserId->fetch('workspace')->shouldBeCalled()->willReturn('USER_ID');

        $this->getBotReactionsForMessageAndUser->fetch('workspace', 'channel', 'message', 'USER_ID')
            ->shouldBeCalled()
            ->willReturn(['ok_hand']);

        $this->slackClient->setReactionsToMessageWith(MessageIdentifier::fromString('workspace@channel@message'), ['ok_hand', 'rocket']);

        $generatedRequest = $this->httpClientMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.add', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel'   => 'channel',
                'timestamp' => 'message',
                'name'      => 'rocket',
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_removes_reactions_from_the_messages(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
        $this->getBotUserId->fetch('workspace')->shouldBeCalled()->willReturn('USER_ID');
        $this->getBotReactionsForMessageAndUser->fetch('workspace', 'channel', 'message', 'USER_ID')
            ->shouldBeCalled()
            ->willReturn(['ok_hand', 'one', 'red_ci']);

        $this->slackClient->setReactionsToMessageWith(
            MessageIdentifier::fromString('workspace@channel@message'),
            ['ok_hand', 'one']
        );

        $generatedRequest = $this->httpClientMock->getLastRequest();
        $this->assertEquals('POST', $generatedRequest->getMethod());
        $this->assertEquals('/api/reactions.remove', $generatedRequest->getUri()->getPath());
        $this->assertEquals(
            [
                'channel'   => 'channel',
                'timestamp' => 'message',
                'name'      => 'red_ci',
            ],
            $this->getBodyContent($generatedRequest)
        );
    }

    /**
     * @test
     */
    public function it_does_not_update_the_reactions(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));
        $this->getBotUserId->fetch('workspace')->shouldBeCalled()->willReturn('USER_ID');
        $this->getBotReactionsForMessageAndUser->fetch('workspace', 'channel', 'message', 'USER_ID')
            ->shouldBeCalled()
            ->willReturn(['ok_hand', 'one', 'red_ci']);

        $this->slackClient->setReactionsToMessageWith(
            MessageIdentifier::fromString('workspace@channel@message'),
            ['ok_hand', 'one', 'red_ci']
        );
        $this->assertNull($this->httpClientMock->getLastRequest());
    }

    /**
     * @test
     */
    public function it_publishes_a_message_in_a_channel(): void
    {
        $message = 'a message';
        $channel = 'workspace@channel';
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));

        $this->slackClient->publishInChannel(ChannelIdentifier::fromString($channel), $message);

        $generatedRequest = $this->httpClientMock->getLastRequest();
        self::assertEquals('POST', $generatedRequest->getMethod());
        self::assertEquals('/api/chat.postMessage', $generatedRequest->getUri()->getPath());
        $bodyContent = $this->getBodyContent($generatedRequest);
        self::assertEquals(
            ['channel' => 'channel', 'text' => $message],
            $bodyContent
        );
    }

    /**
     * @test
     */
    public function it_publishes_an_ephemeral_message(): void
    {
        $url = 'https://slack.ephemeral.url/';
        $message = 'a message';
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));

        $this->slackClient->answerWithEphemeralMessage($url, $message);

        $generatedRequest = $this->httpClientMock->getLastRequest();
        self::assertEquals('POST', $generatedRequest->getMethod());
        self::assertEquals((new Uri($url))->getPath(), $generatedRequest->getUri()->getPath());
        $bodyContent = $this->getBodyContent($generatedRequest);
        self::assertEquals(
            [
                'text' => $message,
                'response_type' => 'ephemeral',
            ],
            $bodyContent
        );
    }

    public function test_it_explains_a_URL_cannot_be_parsed(): void
    {
        $url = 'https://slack.ephemeral.url/';
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));

        $this->slackClient->explainPRURLCannotBeParsed($url, 'a message');

        $generatedRequest = $this->httpClientMock->getLastRequest();
        self::assertEquals('POST', $generatedRequest->getMethod());
        self::assertEquals((new Uri($url))->getPath(), $generatedRequest->getUri()->getPath());
        $bodyContent = $this->getBodyContent($generatedRequest);
        $expectedMessage = <<<TEXT
:warning: `a message`
:thinking_face: Sorry, I was not able to parse the pull request URL, can you check it and try again ?
TEXT;
        self::assertEquals(
            [
                'text' => $expectedMessage,
                'response_type' => 'ephemeral',
            ],
            $bodyContent
        );
    }

    public function test_it_explains_a_the_slack_app_is_not_installed(): void
    {
        $url = 'https://slack.ephemeral.url/';
        $this->mockGuzzleWith(new Response(200, [], '{"ok": true}'));

        $this->slackClient->explainAppNotInstalled($url, 'a message');

        $generatedRequest = $this->httpClientMock->getLastRequest();
        self::assertEquals('POST', $generatedRequest->getMethod());
        self::assertEquals((new Uri($url))->getPath(), $generatedRequest->getUri()->getPath());
        $bodyContent = $this->getBodyContent($generatedRequest);
        $expectedMessage = <<<TEXT
:warning: `a message`
:thinking_face: It looks like Yeee is not installed on this repository but you <https://github.com/apps/slub-yeee|Install it> now!
TEXT;
        self::assertEquals(
            [
                'text' => $expectedMessage,
                'response_type' => 'ephemeral',
            ],
            $bodyContent
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_response_status_code_is_not_success(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->mockGuzzleWith(new Response(400));

        $this->slackClient->answerWithEphemeralMessage('https://slack.ephemeral.url/', 'a message');
    }

    /**
     * @test
     */
    public function it_publishes_a_message_with_blocks(): void
    {
        $workspace = 'workspace';
        $channel = 'channel';
        $channelIdentifier = $workspace .'@'.$channel;
        $messageWithBlocks = ['Ta message containing blocks'];

        $slackTS = 'slack_ts';
        $slackChannel = 'slack_channel';
        $slackWorkspace = 'slack_workspace';
        $apiResponse = [
            'ok' => true,
            'message' => ['team' => $slackWorkspace],
            'channel' => $slackChannel,
            'ts' => $slackTS
        ];
        $this->mockGuzzleWith(new Response(200, $apiResponse, json_encode($apiResponse)));

        $actualMessageIdentifier = $this->slackClient->publishMessageWithBlocksInChannel(ChannelIdentifier::fromString($channelIdentifier), $messageWithBlocks);

        self::assertEquals(MessageIdentifierHelper::from($slackWorkspace, $slackChannel, $slackTS), $actualMessageIdentifier);
        $generatedRequest = $this->httpClientMock->getLastRequest();
        self::assertEquals('POST', $generatedRequest->getMethod());
        self::assertEquals('/api/chat.postMessage', $generatedRequest->getUri()->getPath());
        $bodyContent = $this->getBodyContent($generatedRequest);
        self::assertEquals(
            [
                'channel' => $channel,
                'blocks' => $messageWithBlocks,
                'unfurl_links' => false,
                'link_names' => true
            ],
            $bodyContent
        );
    }

    /**
     * @test
     */
    public function it_throws_is_response_is_not_success_when_it_publishes_a_message_with_blocks(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->mockGuzzleWith(new Response(400, [], json_encode(['ok' => true])));

        $this->slackClient->publishMessageWithBlocksInChannel(
            ChannelIdentifier::fromString('workspace@channel'),
            ['Ta message containing blocks']
        );
    }

    /**
     * @test
     */
    public function it_throws_if_the_http_status_is_not_ok(): void
    {
        $this->mockGuzzleWith(new Response(400, [], ''));

        $this->expectException(\RuntimeException::class);
        $this->slackClient->replyInThread(MessageIdentifier::fromString('workspace@channel@message'), 'hello world');
    }

    /**
     * @test
     */
    public function it_throws_if_the_ok_flag_is_false(): void
    {
        $this->mockGuzzleWith(new Response(200, [], '{"ok": false}'));

        $this->expectException(\RuntimeException::class);
        $this->slackClient->replyInThread(MessageIdentifier::fromString('workspace@channel@message'), 'hello world');
    }

    // It publishes messages in block and returns the message identifier associated

    private function setUpGuzzleMock(): Client
    {
        $this->httpClientMock = new MockHandler([]);
        $handler = HandlerStack::create($this->httpClientMock);

        return new Client(['handler' => $handler]);
    }

    private function getBodyContent($generatedRequest): array
    {
        return json_decode($generatedRequest->getBody()->getContents(), true);
    }

    private function mockGuzzleWith(Response $response): void
    {
        $this->httpClientMock->append($response);
    }

    private function mockSlackAppInstallation(ObjectProphecy $slackAppInstallationRepository): void
    {
        $slackAppInstallation = new SlackAppInstallation();
        $slackAppInstallation->accessToken = 'access_token';
        $slackAppInstallation->workspaceId = 'workspace';
        $slackAppInstallationRepository->getBy('workspace')->willReturn($slackAppInstallation);
    }
}
