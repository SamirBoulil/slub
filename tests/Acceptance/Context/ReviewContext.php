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

    /** @var NewReviewHandler */
    private $reviewHandler;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var ChatClientSpy */
    private $chatClientSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    /** @var MessageIdentifier */
    private $currentMessageIdentifier;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        NewReviewHandler $reviewHandler,
        EventsSpy $eventSpy,
        ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);

        $this->reviewHandler = $reviewHandler;
        $this->eventSpy = $eventSpy;
        $this->chatClientSpy = $chatClientSpy;
    }

    /**
     * @Given /^a PR in review$/
     */
    public function aPullRequestInReview()
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
    public function thePullRequestIsGTMed()
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
    public function thePullRequestShouldBeGTMed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['GTMS']);
    }

    /**
     * @Then /^the squad should be notified that the PR has one more GTM$/
     */
    public function theSquadShouldBeNotifiedThatThePullRequestHasOneMoreGTM()
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
    public function thePullRequestIsNOTGTMED()
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
    public function thePullRequestShouldBeNOTGTMed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['NOT_GTMS']);
    }

    /**
     * @When /^a PR is reviewed on an unsupported repository$/
     */
    public function aPullRequestIsReviewedOnAnUnsupportedRepository()
    {
        $this->currentPRIdentifier = PRIdentifier::fromString('1010');

        $notGTM = new NewReview();
        $notGTM->repositoryIdentifier = 'unsupported_repository';
        $notGTM->PRIdentifier = '1010';
        $notGTM->reviewStatus = 'approved';

        $this->reviewHandler->handle($notGTM);
    }

    /**
     * @Then /^it does not notify the squad$/
     */
    public function itDoesNotNotifyTheSquad()
    {
        Assert::assertNotNull($this->currentPRIdentifier, 'The PR identifier was not created');
        Assert::assertFalse($this->PRExists($this->currentPRIdentifier), 'PR should not exist but was found.');
        Assert::assertFalse($this->eventSpy->PRNotGMTedDispatched(), 'Event has been thrown, while none was expected.');
    }

    private function PRExists(PRIdentifier $PRIdentifier): bool
    {
        $found = true;
        try {
            $this->PRRepository->getBy($PRIdentifier);
        } catch (PRNotFoundException $notFoundException) {
            $found = false;
        }

        return $found;
    }

    /**
     * @When /^the PR is commented$/
     */
    public function thePRIsCommented()
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
    public function thePRShouldHaveOneComment()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['COMMENTS']);
    }

    /**
     * @Given /^the author should be notified that the PR has one more comment$/
     */
    public function theAuthorShouldBeNotifiedThatThePRHasOneMoreComment()
    {
        Assert::assertTrue($this->eventSpy->PRCommentedDispatched());
        $commentMessage = str_replace(NotifyAuthor::PLACEHOLDER_REVIEWER_NAME, self::REVIEWER_NAME, NotifyAuthor::MESSAGE_PR_COMMENTED);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $commentMessage);
    }

    /**
     * @Given /^the author should be notified that the PR has one more GTM$/
     */
    public function theAuthorShouldBeNotifiedThatThePRHasOneMoreGTM()
    {
        Assert::assertTrue($this->eventSpy->PRGMTedDispatched());
        $gtmedMessage = str_replace(NotifyAuthor::PLACEHOLDER_REVIEWER_NAME, self::REVIEWER_NAME, NotifyAuthor::MESSAGE_PR_GTMED);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $gtmedMessage);
    }

    /**
     * @Given /^the author should be notified that the PR has one more NOT GTM$/
     */
    public function theAuthorShouldBeNotifiedThatThePRHasOneMoreNOTGTM()
    {
        Assert::assertTrue($this->eventSpy->PRNotGMTedDispatched());
        $notGtmedMessage = str_replace(NotifyAuthor::PLACEHOLDER_REVIEWER_NAME, self::REVIEWER_NAME, NotifyAuthor::MESSAGE_PR_NOT_GTMED);
        $this->chatClientSpy->assertReaction($this->currentMessageIdentifier, $notGtmedMessage);
    }
}
