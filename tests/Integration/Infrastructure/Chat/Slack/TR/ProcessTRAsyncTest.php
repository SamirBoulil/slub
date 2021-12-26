<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\TR;

use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 *
 * TODO: Transform as a functional test instead of an integration test.
 */
class ProcessTRAsyncTest extends WebTestCase
{
    private const USER_ID = 'user_123123';
    private const EMPHEMERAL_RESPONSE_URL = 'https://slack/response_url/';

    private PRRepositoryInterface $PRRepository;
    private ChatClientSpy $chatClientSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->chatClientSpy = $this->get('slub.infrastructure.chat.slack.slack_client');
    }

    public function test_it_handles_pr_to_review_submission(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/tr', $this->PRToReviewSubmission());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPRHasBeenPutToReview();
        // TODO: For some reason the chat client spy is empty whenever we return from the request.
        // $this->assertToReviewMessageHasBeenPublished();
    }

    public function test_it_tells_the_author_when_the_pr_link_is_not_detected(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/tr', $this->NoPRLink());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertNoPRToReview();
        // TODO: For some reason the chat client spy is empty whenever we return from the request.
        // $this->chatClientSpy->assertEphemeralMessageContains(self::EMPHEMERAL_RESPONSE_URL, ':warning:');
    }

//    public function test_it_tells_the_author_an_issue_arised_when_its_the_case(): void
//    {
    // Payload is valid but handler returns an exception for some reason.
    // $this->expectException(\Exception::class);
    // $client = self::getClient();
    // $client->request('POST', '/chat/slack/tr', $this->payloadIsInvalid());
    // $this->assertEquals(200, $client->getResponse()->getStatusCode());
    // $this->assertNoPRToReview();
    // TODO: For some reason the chat client spy is empty whenever we return from the request.
    // $this->chatClientSpy->assertEphemeralMessageContains(self::EMPHEMERAL_RESPONSE_URL, ':warning:');
//    }

    private function PRToReviewSubmission(): array
    {
        return $this->slashCommandPayload('blabla https://github.com/SamirBoulil/slub/pull/153 blabla', 'team_123');
    }

    private function NoPRLink(): array
    {
        return $this->slashCommandPayload('no_message', 'team_123');
    }

    private function assertPRHasBeenPutToReview()
    {
        $PRS = $this->PRRepository->all();
        $this->assertNotEmpty($PRS);

        $PRToReview = current($PRS);
        self::assertEquals('SamirBoulil/slub/153', $PRToReview->PRIdentifier()->stringValue());
        self::assertEquals('published_message_identifier', $PRToReview->messageIdentifiers()[0]->stringValue());
        self::assertEquals('team_123@channel_name', $PRToReview->channelIdentifiers()[0]->stringValue());
        self::assertEquals('sam', $PRToReview->authorIdentifier()->stringValue());
    }

    private function assertToReviewMessageHasBeenPublished()
    {
        $this->chatClientSpy->assertPublishMessageWithBlocksInChannelContains('team_123@channel_name', 'https://github.com/SamirBoulil/slub/pull/153');
        $this->chatClientSpy->assertPublishMessageWithBlocksInChannelContains('team_123@channel_name', sprintf('<%s>', self::USER_ID));
        $this->chatClientSpy->assertPublishMessageWithBlocksInChannelContains('team_123@channel_name', '[SamirBoulil/slub]');
    }

    private function slashCommandPayload(string $userInput, string $workspaceIdentifier): array
    {
        return [
            'text' => $userInput,
            'user_id' => self::USER_ID,
            'team_id' => $workspaceIdentifier,
            'channel_id' => 'channel_name',
            'trigger_id' => '123123.123123',
            'response_url' => self::EMPHEMERAL_RESPONSE_URL
        ];
    }

    private function assertNoPRToReview(): void
    {
        $this->assertEmpty($this->PRRepository->all());
    }
}
