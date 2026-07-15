<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\TR;

use Slub\Domain\Repository\DocumentRepositoryInterface;
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
    private DocumentRepositoryInterface $documentRepository;
    private ChatClientSpy $chatClientSpy;

    public function setUp(): void
    {
        parent::setUp();
        $this->PRRepository = $this->get('slub.infrastructure.persistence.pr_repository');
        $this->documentRepository = $this->get('slub.infrastructure.persistence.document_repository');
        $this->chatClientSpy = $this->get('slub.infrastructure.chat.slack.slack_client');
    }

    public function test_it_handles_pr_to_review_submission(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/tr', $this->PRToReviewSubmission('https://github.com/SamirBoulil/slub/pull/153'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPRHasBeenPutToReview();
        // TODO: For some reason the chat client spy is empty whenever we return from the request.
        // $this->assertToReviewMessageHasBeenPublished();
    }

    public function test_it_handles_pr_to_review_submission_with_shorten_url(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/tr', $this->PRToReviewSubmission('github.com/SamirBoulil/slub/pull/153'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPRHasBeenPutToReview();
        // TODO: For some reason the chat client spy is empty whenever we return from the request.
        // $this->assertToReviewMessageHasBeenPublished();
    }

    public function test_it_handles_document_to_review_submission(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/tr', $this->PRToReviewSubmission('https://www.notion.so/xxx/my-super-doc?p=12345678901234567890123456789012#top'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertDocumentHasBeenPutToReview();
    }

    private function PRToReviewSubmission(string $url): array
    {
        return $this->slashCommandPayload('blabla '.$url.' blabla', 'team_123');
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

    private function assertDocumentHasBeenPutToReview()
    {
        $documents = $this->documentRepository->all();
        $this->assertNotEmpty($documents);

        $document = current($documents);
        self::assertEquals('https://www.notion.so/xxx/my-super-doc?p=12345678901234567890123456789012#top', $document->url()->asString());
        self::assertEquals('team_123@channel_name', $document->channelIdentifiers()[0]->stringValue());
        self::assertEquals('published_message_identifier', $document->messageIdentifiers()[0]->stringValue());
        self::assertEquals(self::USER_ID, $document->authorIdentifier()->stringValue());
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
}
