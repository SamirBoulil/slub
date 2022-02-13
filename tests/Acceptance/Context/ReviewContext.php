<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Application\Notify\NotifyAuthor;
use Slub\Application\Notify\NotifySquad;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

class ReviewContext extends FeatureContext
{
    private const REVIEWER_NAME = 'samir';

    private PRIdentifier $currentPRIdentifier;

    private MessageIdentifier $currentMessageIdentifier;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private NewReviewHandler $reviewHandler,
        private EventsSpy $eventSpy,
        private ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
    }

    /**
     * @Given /^a PR in review$/
     */
    public function aPullRequestInReview(): void
    {
        $this->currentPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $this->currentMessageIdentifier = MessageIdentifier::fromString('CHANNEL_ID@1');
        $this->PRRepository->save(PR::create(
            $this->currentPRIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        ));
        $this->chatClientSpy->reset();
    }

    /**
     * @When /^the PR is GTMed$/
     */
    public function thePullRequestIsGTMed(): void
    {
        $gtm = new NewReview();
        $gtm->repositoryIdentifier = 'akeneo/pim-community-dev';
        $gtm->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $gtm->reviewerName = self::REVIEWER_NAME;
        $gtm->reviewStatus = 'accepted';
        $this->reviewHandler->handle($gtm);
    }

    /**
     * @Then /^the PR should be GTMed$/
     */
    public function thePullRequestShouldBeGTMed(): void
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['GTMS']);
    }

    /**
     * @Then /^the squad should be notified that the PR has one more GTM$/
     */
    public function theSquadShouldBeNotifiedThatThePullRequestHasOneMoreGTM(): void
    {
        Assert::assertNotNull($this->currentPRIdentifier, 'The PR identifier was not created');
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        $GTMCount = $PR->normalize()['GTMS'];
        Assert::assertEquals(1, $GTMCount, sprintf('The PR has %d GTMS, expected %d', $GTMCount, 1));
        Assert::assertTrue($this->eventSpy->PRGMTedDispatched());
        $this->chatClientSpy->assertReaction(
            $this->currentMessageIdentifier,
            NotifySquad::REACTION_PR_REVIEWED[1]
        );
    }

    /**
     * @When /^the PR is NOT GTMED$/
     */
    public function thePullRequestIsNOTGTMED(): void
    {
        $notGTM = new NewReview();
        $notGTM->repositoryIdentifier = 'akeneo/pim-community-dev';
        $notGTM->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $notGTM->reviewerName = self::REVIEWER_NAME;
        $notGTM->reviewStatus = 'refused';
        $this->reviewHandler->handle($notGTM);
    }

    /**
     * @Then /^the PR should be NOT GTMed$/
     */
    public function thePullRequestShouldBeNOTGTMed(): void
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['NOT_GTMS']);
    }

    private function PRExists(PRIdentifier $PRIdentifier): bool
    {
        $found = true;
        try {
            $this->PRRepository->getBy($PRIdentifier);
        } catch (PRNotFoundException) {
            $found = false;
        }

        return $found;
    }

    /**
     * @When /^the PR is commented$/
     */
    public function thePRIsCommented(): void
    {
        $comment = new NewReview();
        $comment->repositoryIdentifier = 'akeneo/pim-community-dev';
        $comment->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $comment->reviewerName = self::REVIEWER_NAME;
        $comment->reviewStatus = 'commented';
        $this->reviewHandler->handle($comment);
    }

    /**
     * @Then /^the PR should have one comment$/
     */
    public function thePRShouldHaveOneComment(): void
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['COMMENTS']);
    }

    /**
     * @Given /^the author should be notified that the PR has one more comment$/
     */
    public function theAuthorShouldBeNotifiedThatThePRHasOneMoreComment(): void
    {
        Assert::assertTrue($this->eventSpy->PRCommentedDispatched());
        $commentMessage = str_replace(NotifyAuthor::PLACEHOLDER_REVIEWER_NAME, self::REVIEWER_NAME, NotifyAuthor::MESSAGE_PR_COMMENTED);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $commentMessage);
    }

    /**
     * @Given /^the author should be notified that the PR has one more GTM$/
     */
    public function theAuthorShouldBeNotifiedThatThePRHasOneMoreGTM(): void
    {
        Assert::assertTrue($this->eventSpy->PRGMTedDispatched());
        $gtmedMessage = str_replace(NotifyAuthor::PLACEHOLDER_REVIEWER_NAME, self::REVIEWER_NAME, NotifyAuthor::MESSAGE_PR_GTMED);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $gtmedMessage);
    }

    /**
     * @Given /^the author should be notified that the PR has one more NOT GTM$/
     */
    public function theAuthorShouldBeNotifiedThatThePRHasOneMoreNOTGTM(): void
    {
        Assert::assertTrue($this->eventSpy->PRNotGMTedDispatched());
        $notGtmedMessage = str_replace(NotifyAuthor::PLACEHOLDER_REVIEWER_NAME, self::REVIEWER_NAME, NotifyAuthor::MESSAGE_PR_NOT_GTMED);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $notGtmedMessage);
    }
}
