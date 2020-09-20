<?php

namespace Tests\Acceptance\Context;

use Ramsey\Uuid\Uuid;
use Slub\Application\PublishReminders\PublishRemindersHandler;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
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

    /** @var ChatClientSpy */
    private $chatClientSpy;

    /** @var PublishRemindersHandler */
    private $publishRemindersHandler;

    /** @var InMemoryClock */
    private $clock;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        PublishRemindersHandler $publishRemindersHandler,
        ChatClientSpy $chatClientSpy,
        InMemoryClock $clock
    ) {
        parent::__construct($PRRepository);

        $this->chatClientSpy = $chatClientSpy;
        $this->publishRemindersHandler = $publishRemindersHandler;
        $this->clock = $clock;
    }

    /**
     * @Given /^some PRs in review and some PRs merged in multiple channels$/
     */
    public function somePRsInReviewAndSomePRsMergedInMultipleChannels()
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
    public function anAuthorPublishesAReminder()
    {
        $this->publishRemindersHandler->handle();
    }

    /**
     * @Given /^the reminders should only contain a reference to the PRs in review$/
     */
    public function theRemindersShouldOnlyContainAReferenceToThePRsInReview()
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndMessage(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            <<<CHAT
Yop, these PRs need reviews!
 - *Sam*, _<https://github.com/samirboulil/slub/pull/1|"Add new feature">_ (Today)
 - *Sam*, _<https://github.com/samirboulil/slub/pull/2|"Add new feature">_ (Yesterday)
CHAT
        );
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndMessage(
            ChannelIdentifier::fromString(self::GENERAL),
            <<<CHAT
Yop, these PRs need reviews!
 - *Sam*, _<https://github.com/samirboulil/slub/pull/3|"Add new feature">_ (2 days ago)
CHAT
        );
    }

    /**
     * @Given /^a PR in review not GTMed$/
     * @Given /^a PR not GTMed published in a supported channel$/
     */
    public function aPRInReviewHavingNoGTMs()
    {
        $this->createInReviewPR(self::PR_1, self::SQUAD_RACCOONS, 0, 0);
    }

    /**
     * @Given /^a PR in review having (\d+) GTMs$/
     */
    public function aPRInReviewHavingGTMs(int $numberOfGTMs)
    {
        $this->createInReviewPR(self::PR_2, self::SQUAD_RACCOONS, $numberOfGTMs, 0);
    }

    /**
     * @Given /^a PR merged$/
     */
    public function aPRMerged()
    {
        $this->createMergedPR(self::SQUAD_RACCOONS);
    }

    /**
     * @Then /^the reminder should only contain the PR not GTMed$/
     * @Then /^the reminder should only contain the PR not GTMed in the supported channel$/
     */
    public function theReminderShouldOnlyContainThePRInReviewHavingGTMs(): void
    {
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifierAndMessage(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            <<<CHAT
Yop, these PRs need reviews!
 - *Sam*, _<https://github.com/samirboulil/slub/pull/1|"Add new feature">_ (Today)
CHAT
        );
    }

    /**
     * @Given /^a PR not GTMed published in a unsupported channel$/
     */
    public function aPRNotGTMedPublishedInAUnsupportedChannel()
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
    public function aPRClosed()
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
    public function weAreOnAWeekEnd()
    {
        $this->clock->YesWeAReOneWeekEnd();
    }

    /**
     * @Then /^the reminder should be empty$/
     */
    public function theReminderShouldBeEmpty()
    {
        $this->chatClientSpy->assertEmpty();
    }
}
