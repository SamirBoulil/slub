<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Chat\Slack\UnTR;

use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\Document\DocumentIdentifier;
use Slub\Domain\Entity\Document\DocumentURL;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\DocumentRepositoryInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\WebTestCase;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 *
 * TODO: Transform as a functional test instead of an integration test.
 */
class ProcessUnTRAsyncTest extends WebTestCase
{
    private const USER_ID = 'user_123123';
    private const EMPHEMERAL_RESPONSE_URL = 'https://slack/response_url/';
    private const DOCUMENT_URL = 'https://www.notion.so/xxx/my-super-doc';

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

    public function test_it_handles_unpublishing_pr_requests(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/untr', $this->PRToUnpublish());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertPRIsNotInReview();
        // TODO: For some reason the chat client spy is empty whenever we return from the request.
        // $this->assertToReviewMessageHasBeenPublished();
    }

    public function test_it_handles_unpublishing_document_requests(): void
    {
        $this->createDocumentInReview(self::DOCUMENT_URL);
        $this->createPRInReview();

        $client = self::getClient();
        $client->request('POST', '/chat/slack/untr', $this->documentToUnpublish(self::DOCUMENT_URL));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertDocumentIsNotInReview();
        $this->assertCount(1, $this->PRRepository->all());
    }

    public function test_it_confirms_even_when_the_document_was_never_put_to_review(): void
    {
        $client = self::getClient();
        $client->request('POST', '/chat/slack/untr', $this->documentToUnpublish(self::DOCUMENT_URL));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertDocumentIsNotInReview();
    }

    public function test_it_answers_with_an_error_when_the_text_contains_no_url(): void
    {
        $this->createDocumentInReview(self::DOCUMENT_URL);

        $client = self::getClient();
        $client->request('POST', '/chat/slack/untr', $this->slashCommandPayload('yada yada', 'team_123'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(1, $this->documentRepository->all());
    }

    private function PRToUnpublish(): array
    {
        return $this->slashCommandPayload('blabla https://github.com/SamirBoulil/slub/pull/153 blabla', 'team_123');
    }

    private function documentToUnpublish(string $url): array
    {
        return $this->slashCommandPayload('blabla '.$url.' blabla', 'team_123');
    }

    private function assertPRIsNotInReview()
    {
        $PRS = $this->PRRepository->all();
        $this->assertEmpty($PRS);
    }

    private function assertDocumentIsNotInReview(): void
    {
        $this->assertEmpty($this->documentRepository->all());
    }

    private function createDocumentInReview(string $url): void
    {
        $documentURL = new DocumentURL($url);
        $this->documentRepository->save(
            Document::create(
                DocumentIdentifier::fromURL($documentURL),
                $documentURL,
                ChannelIdentifier::fromString('team_123@channel_name'),
                WorkspaceIdentifier::fromString('team_123'),
                MessageIdentifier::fromString('team_123@channel_name@1111.2222'),
                AuthorIdentifier::fromString(self::USER_ID),
            )
        );
    }

    private function createPRInReview(): void
    {
        $this->PRRepository->save(
            PR::create(
                PRIdentifier::create('SamirBoulil/slub/153'),
                ChannelIdentifier::fromString('team_123@channel_name'),
                WorkspaceIdentifier::fromString('team_123'),
                MessageIdentifier::fromString('team_123@channel_name@5555.6666'),
                AuthorIdentifier::fromString('sam'),
                Title::fromString('Add new feature')
            )
        );
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
