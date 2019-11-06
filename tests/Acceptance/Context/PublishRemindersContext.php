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
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;

class PublishRemindersContext extends FeatureContext
{
    private const SQUAD_RACCOONS = 'squad-raccoons';
    private const GENERAL = 'general';
    private const UNSUPPORTED_CHANNEL = 'UNSUPPORTED_CHANNEL';

    /** @var ChatClientSpy */
    private $chatClientSpy;

    /** @var PublishRemindersHandler */
    private $publishRemindersHandler;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        PublishRemindersHandler $publishRemindersHandler,
        ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);

        $this->chatClientSpy = $chatClientSpy;
        $this->publishRemindersHandler = $publishRemindersHandler;
    }

    /**
     * @Given /^some PRs in review and some PRs merged in multiple channels$/
     */
    public function somePRsInReviewAndSomePRsMergedInMultipleChannels()
    {
        $this->createMergedPR(self::SQUAD_RACCOONS);
        $this->createMergedPR(self::SQUAD_RACCOONS);
        $this->createInReviewPR('samirboulil/slub/1', self::SQUAD_RACCOONS, 0);
        $this->createInReviewPR('samirboulil/slub/2', self::SQUAD_RACCOONS, 0);

        $this->createMergedPR(self::GENERAL);
        $this->createMergedPR(self::GENERAL);
        $this->createInReviewPR('samirboulil/slub/3', self::GENERAL, 0);
        $this->createInReviewPR('samirboulil/slub/4', self::GENERAL, 0);
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
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifier(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            <<<CHAT
Yop, these PRs need reviews!
 - https://github.com/samirboulil/slub/pull/1
 - https://github.com/samirboulil/slub/pull/2
CHAT
        );
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifier(
            ChannelIdentifier::fromString(self::GENERAL),
            <<<CHAT
Yop, these PRs need reviews!
 - https://github.com/samirboulil/slub/pull/3
 - https://github.com/samirboulil/slub/pull/4
CHAT
        );
    }

    /**
     * @Given /^a PR in review not GTMed$/
     * @Given /^a PR not GTMed published in a supported channel$/
     */
    public function aPRInReviewHavingNoGTMs()
    {
        $this->createInReviewPR('samirboulil/slub/1', self::SQUAD_RACCOONS, 0);
    }

    /**
     * @Given /^a PR in review having (\d+) GTMs$/
     */
    public function aPRInReviewHavingGTMs(int $numberOfGTMs)
    {
        $this->createInReviewPR('samirboulil/slub/2', self::SQUAD_RACCOONS, $numberOfGTMs);
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
        $this->chatClientSpy->assertHasBeenCalledWithChannelIdentifier(
            ChannelIdentifier::fromString(self::SQUAD_RACCOONS),
            <<<CHAT
Yop, these PRs need reviews!
 - https://github.com/samirboulil/slub/pull/1
CHAT
        );
    }

    /**
     * @Given /^a PR not GTMed published in a unsupported channel$/
     */
    public function aPRNotGTMedPublishedInAUnsupportedChannel()
    {
        $this->createInReviewPR('samirboulil/slub/5', self::UNSUPPORTED_CHANNEL, 0);
    }

    private function createMergedPR($channelIdentifier): void
    {
        $PR = PR::create(
            PRIdentifier::create(Uuid::uuid4()->toString()),
            ChannelIdentifier::fromString($channelIdentifier),
            MessageIdentifier::fromString(Uuid::uuid4()->toString()),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->merged();
        $this->PRRepository->save($PR);
    }

    private function createInReviewPR(string $PRIdentifier, string $channel, $GTMs): void
    {
        $PR = PR::create(
            PRIdentifier::create($PRIdentifier),
            ChannelIdentifier::fromString($channel),
            MessageIdentifier::fromString(Uuid::uuid4()->toString()),
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );

        for ($i = 0; $i < $GTMs; $i++) {
            $PR->GTM();
        }

        $this->PRRepository->save($PR);
    }
}
