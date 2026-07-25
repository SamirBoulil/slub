<?php

namespace Tests\Acceptance\Context;

use Ramsey\Uuid\Uuid;
use Slub\Application\PublishReminders\PublishRemindersHandler;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\Document\Document;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\DocumentRepositoryInterface;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Persistence\InMemory\Query\InMemoryClock;
use Tests\Acceptance\helpers\ChatClientSpy;

class PublishRemindersContext extends FeatureContext
{
    private const SQUAD_RACCOONS = 'squad-raccoons';
    private const GENERAL = 'general';
    private const UNSUPPORTED_CHANNEL = 'UNSUPPORTED_CHANNEL';
    private const PR_1 = 'samirboulil/slub/1';
    private const PR_2 = 'samirboulil/slub/2';
    private const PR_3 = 'samirboulil/slub/3';
    private const DOC_URL_1 = 'https://www.notion.so/xxx/my-doc';
    private const DOC_MSG_1 = 'akeneo@squad-raccoons@1111.2222';
    private const DOC_URL_2 = 'https://www.notion.so/xxx/my-doc-2';
    private const DOC_MSG_2 = 'akeneo@squad-raccoons@3333.4444';
    private const CHECK_MARK = 'white_check_mark';

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private PublishRemindersHandler $publishRemindersHandler,
        private ChatClientSpy $chatClientSpy,
        private InMemoryClock $clock,
        private DocumentRepositoryInterface $documentRepository
    ) {
        parent::__construct($PRRepository);
    }

    /**
     * @BeforeScenario
     */
    public function cleanDocuments(): void
    {
        $this->documentRepository->reset();
    }

    /**
     * @Given /^some PRs in review and some PRs merged in multiple channels$/
     */
    public function somePRsInReviewAndSomePRsMergedInMultipleChannels(): void
    {
        $this->createMergedPR(self::SQUAD_RACCOONS);
        $this->createClosedPRNotMerged(self::SQUAD_RACCOONS);
        $this->createInReviewPR(self::PR_2, self::SQUAD_RACCOONS, 0, 1);
        $this->createInReviewPR(self::PR_1, self::SQUAD_RACCOONS, 0, 0);

        $this->createMergedPR(self::GENERAL);
        $this->createInReviewPR(self::PR_3, self::GENERAL, 0, 2);
    }

    /**
     * @When /^the system publishes a reminder$/
     */
    public function anAuthorPublishesAReminder(): void
    {
        $this->publishRemindersHandler->handle();
    }

    /**
     * @Given /^the reminders should only contain a reference to the PRs in review$/
     */
    public function theRemindersShouldOnlyContainAReferenceToThePRsInReview(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessage(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            json_decode('[[{"type":"section","text":{"type":"mrkdwn","text":"Yeee, these PRs need reviews!"}},{"type":"context","elements":[{"type":"image","image_url":"https:\/\/avatars.githubusercontent.com\/Sam","alt_text":"Sam is the author of the PR"},{"type":"mrkdwn","text":"*<https:\/\/github.com\/samirboulil\/slub\/pull\/1|Add new feature>*, _Today_"}]},{"type":"context","elements":[{"type":"image","image_url":"https:\/\/avatars.githubusercontent.com\/Sam","alt_text":"Sam is the author of the PR"},{"type":"mrkdwn","text":"*<https:\/\/github.com\/samirboulil\/slub\/pull\/2|Add new feature>*, _Yesterday_"}]}]]', true)
        );
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessage(
            ChannelIdentifier::fromString(self::GENERAL),
            json_decode('[[{"type":"section","text":{"type":"mrkdwn","text":"Yeee, these PRs need reviews!"}},{"type":"context","elements":[{"type":"image","image_url":"https:\/\/avatars.githubusercontent.com\/Sam","alt_text":"Sam is the author of the PR"},{"type":"mrkdwn","text":"*<https:\/\/github.com\/samirboulil\/slub\/pull\/3|Add new feature>*, _2 days ago_"}]}]]', true)
        );
    }

    /**
     * @Given /^a PR in review not GTMed$/
     * @Given /^a PR not GTMed published in a supported channel$/
     */
    public function aPRInReviewHavingNoGTMs(): void
    {
        $this->createInReviewPR(self::PR_1, self::SQUAD_RACCOONS, 0, 0);
    }

    /**
     * @Given /^a PR in review having (\d+) GTMs$/
     */
    public function aPRInReviewHavingGTMs(int $numberOfGTMs): void
    {
        $this->createInReviewPR(self::PR_2, self::SQUAD_RACCOONS, $numberOfGTMs, 0);
    }

    /**
     * @Given /^a PR merged$/
     */
    public function aPRMerged(): void
    {
        $this->createMergedPR(self::SQUAD_RACCOONS);
    }

    /**
     * @Then /^the reminder should only contain the PR not GTMed$/
     * @Then /^the reminder should only contain the PR not GTMed in the supported channel$/
     */
    public function theReminderShouldOnlyContainThePRInReviewHavingGTMs(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessage(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            json_decode('[[{"type":"section","text":{"type":"mrkdwn","text":"Yeee, these PRs need reviews!"}},{"type":"context","elements":[{"type":"image","image_url":"https:\/\/avatars.githubusercontent.com\/Sam","alt_text":"Sam is the author of the PR"},{"type":"mrkdwn","text":"*<https:\/\/github.com\/samirboulil\/slub\/pull\/1|Add new feature>*, _Today_"}]}]]', true),
        );
    }

    /**
     * @Given /^a PR not GTMed published in a unsupported channel$/
     */
    public function aPRNotGTMedPublishedInAUnsupportedChannel(): void
    {
        $this->createInReviewPR('samirboulil/slub/5', self::UNSUPPORTED_CHANNEL, 0, 0);
    }

    private function createMergedPR($channelIdentifier): void
    {
        $PR = PR::create(
            PRIdentifier::create(Uuid::uuid4()->toString()),
            ChannelIdentifier::fromString($channelIdentifier),
            WorkspaceIdentifier::fromString(Uuid::uuid4()->toString()),
            MessageIdentifier::fromString(Uuid::uuid4()->toString()),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->close(true);
        $this->PRRepository->save($PR);
    }

    private function createInReviewPR(
        string $PRIdentifier,
        string $channelIdentifier,
        int $GTMs,
        int $putInReviewDaysAgo
    ): void {
        $putToReviewTimestamp = (string) (new \DateTime('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d day', $putInReviewDaysAgo))
            ->getTimestamp();
        $PR = PR::fromNormalized([
                'IDENTIFIER' => $PRIdentifier,
                'AUTHOR_IDENTIFIER' => 'sam',
                'TITLE' => 'Add new feature',
                'GTMS' => $GTMs,
                'NOT_GTMS' => 1,
                'COMMENTS' => 1,
                'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                'IS_MERGED' => false,
                'MESSAGE_IDS' => [Uuid::uuid4()->toString()],
                'CHANNEL_IDS' => [$channelIdentifier],
                'WORKSPACE_IDS' => [Uuid::uuid4()->toString()],
                'PUT_TO_REVIEW_AT' => $putToReviewTimestamp,
                'CLOSED_AT' => null,
                 'IS_TOO_LARGE' => false
            ]
        );
        $this->PRRepository->save($PR);
    }

    private function createClosedPRNotMerged(string $channelIdentifier): void
    {
        $PR = PR::create(
            PRIdentifier::create(Uuid::uuid4()->toString()),
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            WorkspaceIdentifier::fromString(Uuid::uuid4()->toString()),
            MessageIdentifier::fromString(Uuid::uuid4()->toString()),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->close(false);
        $this->PRRepository->save($PR);
    }

    /**
     * @Given /^a PR closed$/
     */
    public function aPRClosed(): void
    {
        $PR = PR::create(
            PRIdentifier::create(Uuid::uuid4()->toString()),
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            WorkspaceIdentifier::fromString(Uuid::uuid4()->toString()),
            MessageIdentifier::fromString(Uuid::uuid4()->toString()),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->close(false);
        $this->PRRepository->save($PR);
    }

    /**
     * @Given /^we are on a week\-end$/
     */
    public function weAreOnAWeekEnd(): void
    {
        $this->clock->YesWeAReOneWeekEnd();
    }

    /**
     * @Then /^the reminder should be empty$/
     */
    public function theReminderShouldBeEmpty(): void
    {
        $this->chatClientSpy->assertEmpty();
    }

    /**
     * @Given /^a document in review having (\d+) check mark reactions$/
     */
    public function aDocumentInReviewHavingCheckMarkReactions(int $numberOfCheckMarks): void
    {
        $this->createInReviewDocument(self::DOC_URL_1, self::DOC_MSG_1, self::SQUAD_RACCOONS, 0);
        $this->chatClientSpy->stubReactionCount(self::DOC_MSG_1, self::CHECK_MARK, $numberOfCheckMarks);
    }

    /**
     * @Given /^a document put in review (\d+) days ago$/
     */
    public function aDocumentPutInReviewDaysAgo(int $numberOfDaysAgo): void
    {
        $this->createInReviewDocument(self::DOC_URL_2, self::DOC_MSG_2, self::SQUAD_RACCOONS, $numberOfDaysAgo);
    }

    /**
     * @Given /^a document in review for which Slack fails$/
     */
    public function aDocumentInReviewForWhichSlackFails(): void
    {
        $this->createInReviewDocument(self::DOC_URL_2, self::DOC_MSG_2, self::SQUAD_RACCOONS, 1);
        $this->chatClientSpy->stubReactionCount(
            self::DOC_MSG_2,
            self::CHECK_MARK,
            new \RuntimeException('Slack API failure')
        );
    }

    /**
     * @Then /^the reminder should only contain the document in review$/
     */
    public function theReminderShouldOnlyContainTheDocumentInReview(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessageInOrder(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            [
                [
                    $this->documentsHeaderBlock(),
                    $this->documentBlock(self::DOC_URL_1, self::DOC_MSG_1, 2, 'Today'),
                ],
            ]
        );
    }

    /**
     * @Then /^the reminder should contain both the PR and the document in review$/
     */
    public function theReminderShouldContainBothThePRAndTheDocumentInReview(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessageInOrder(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            [
                [
                    $this->prsHeaderBlock(),
                    $this->prBlock('https://github.com/samirboulil/slub/pull/1', 'Today'),
                    $this->documentsHeaderBlock(),
                    $this->documentBlock(self::DOC_URL_1, self::DOC_MSG_1, 2, 'Today'),
                ],
            ]
        );
    }

    /**
     * @Then /^the reminder should contain the document in review and a degraded line for the failing document$/
     */
    public function theReminderShouldContainTheDocumentInReviewAndADegradedLineForTheFailingDocument(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessageInOrder(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            [
                [
                    $this->documentsHeaderBlock(),
                    $this->documentBlock(self::DOC_URL_1, self::DOC_MSG_1, 2, 'Today'),
                    $this->degradedDocumentBlock(self::DOC_URL_2, 'Yesterday'),
                ],
            ]
        );
    }

    /**
     * @Then /^the reminder should contain the documents ordered by the number of days in review$/
     */
    public function theReminderShouldContainTheDocumentsOrderedByTheNumberOfDaysInReview(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndBlockMessageInOrder(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            [
                [
                    $this->documentsHeaderBlock(),
                    $this->documentBlock(self::DOC_URL_1, self::DOC_MSG_1, 2, 'Today'),
                    $this->documentBlock(self::DOC_URL_2, self::DOC_MSG_2, 0, '2 days ago'),
                ],
            ]
        );
    }

    private function createInReviewDocument(
        string $url,
        string $messageId,
        string $channelIdentifier,
        int $putInReviewDaysAgo
    ): void {
        $putToReviewTimestamp = (string) (new \DateTime('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d day', $putInReviewDaysAgo))
            ->getTimestamp();
        $this->documentRepository->save(
            Document::fromNormalized([
                'IDENTIFIER' => md5($url),
                'URL' => $url,
                'AUTHOR_IDENTIFIER' => 'sam',
                'CHANNEL_IDS' => [$channelIdentifier],
                'WORKSPACE_IDS' => ['akeneo'],
                'MESSAGE_IDS' => [$messageId],
                'PUT_TO_REVIEW_AT' => $putToReviewTimestamp,
            ])
        );
    }

    private function prsHeaderBlock(): array
    {
        return [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'Yeee, these PRs need reviews!',
            ],
        ];
    }

    private function documentsHeaderBlock(): array
    {
        return [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'Yeee, these documents need reviews!',
            ],
        ];
    }

    private function prBlock(string $githubLink, string $timeInReview): array
    {
        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'image',
                    'image_url' => 'https://avatars.githubusercontent.com/Sam',
                    'alt_text' => 'Sam is the author of the PR',
                ],
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf('*<%s|Add new feature>*, _%s_', $githubLink, $timeInReview),
                ],
            ],
        ];
    }

    private function documentBlock(string $url, string $messageId, int $numberOfCheckMarks, string $timeInReview): array
    {
        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        '*<%s|Document>* (<https://slack.example.com/permalink/%s|View message>), %d :white_check_mark:, _%s_',
                        $url,
                        $messageId,
                        $numberOfCheckMarks,
                        $timeInReview
                    ),
                ],
            ],
        ];
    }

    private function degradedDocumentBlock(string $url, string $timeInReview): array
    {
        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf('*<%s|Document>*, _%s_', $url, $timeInReview),
                ],
            ],
        ];
    }
}
