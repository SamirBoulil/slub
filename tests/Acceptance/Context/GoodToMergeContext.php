<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Application\Notify\NotifyAuthor;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Reviewer\ReviewerName;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

class GoodToMergeContext extends FeatureContext
{
    private const PR_IDENTIFIER = 'akeneo/pim-community-dev/1234';

    private MessageIdentifier $currentMessageIdentifier;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private NewReviewHandler $reviewHandler,
        private CIStatusUpdateHandler $CIStatusUpdateHandler,
        private EventsSpy $eventSpy,
        private ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
    }

    /**
     * @Given /^a green PR missing one GTM$/
     */
    public function aGreenPRMissingOneGTM(): void
    {
        $this->currentMessageIdentifier = MessageIdentifier::fromString('CHANNEL_ID@1');

        $PR = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString(Uuid::uuid4()->toString()),
            WorkspaceIdentifier::fromString(Uuid::uuid4()->toString()),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->green();
        $PR->GTM(ReviewerName::fromString('lucie'));
        $this->PRRepository->save($PR);
    }

    /**
     * @When /^the PR gets its last GTM$/
     */
    public function thePRGetsItsLastGTM(): void
    {
        $lastGTM = new NewReview();
        $lastGTM->repositoryIdentifier = 'akeneo/pim-community-dev';
        $lastGTM->PRIdentifier = self::PR_IDENTIFIER;
        $lastGTM->reviewerName = 'martin';
        $lastGTM->reviewStatus = 'accepted';
        $this->reviewHandler->handle($lastGTM);
    }

    /**
     * @Then /^the author should be notified that the PR is good to merge$/
     */
    public function theAuthorShouldBeNotifiedThatThePRIsGoodToMerge(): void
    {
        Assert::assertTrue($this->eventSpy->PRGoodToMergeDispatched(), 'Expect a Good To Merge event to be dispatched');
        $PRLink = 'https://github.com/akeneo/pim-community-dev/pull/1234';
        $goodToMergeMessage = str_replace(NotifyAuthor::PLACEHOLDER_PR_LINK, $PRLink, NotifyAuthor::MESSAGE_GOOD_TO_MERGE);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $goodToMergeMessage);
    }

    /**
     * @Given /^a PR having 2 GTMS$/
     */
    public function aPRHavingGTMS(): void
    {
        $this->currentMessageIdentifier = MessageIdentifier::fromString('CHANNEL_ID@1');

        $PR = PR::create(
            PRIdentifier::create(self::PR_IDENTIFIER),
            ChannelIdentifier::fromString(Uuid::uuid4()->toString()),
            WorkspaceIdentifier::fromString(Uuid::uuid4()->toString()),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->GTM(ReviewerName::fromString('lucie'));
        $PR->GTM(ReviewerName::fromString('martin'));
        $this->PRRepository->save($PR);
    }

    /**
     * @When /^the PR gets a green CI$/
     */
    public function thePRGetsAGreenCI(): void
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'akeneo/pim-community-dev';
        $CIStatusUpdate->PRIdentifier = self::PR_IDENTIFIER;
        $CIStatusUpdate->status = 'GREEN';
        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }
}
