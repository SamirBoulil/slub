<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Application\Notify\NotifyAuthor;
use Slub\Application\Notify\NotifySquad;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Entity\Workspace\WorkspaceIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class CIStatusUpdateContext extends FeatureContext
{
    private const BUILD_LINK = 'https://my-ci.com/build/123';

    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

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
        CIStatusUpdateHandler $CIStatusUpdateHandler,
        EventsSpy $eventSpy,
        ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->eventSpy = $eventSpy;
        $this->PRRepository = $PRRepository;
        $this->chatClientSpy = $chatClientSpy;
    }

    /**
     * @Given /^a PR in review waiting for the CI results$/
     */
    public function aPRInReviewWaitingForTheCIResults()
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
     * @When /^the CI is green for the PR$/
     */
    public function theCIIsGreenForThePullRequest()
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'akeneo/pim-community-dev';
        $CIStatusUpdate->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $CIStatusUpdate->status = 'GREEN';
        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the PR should be green$/
     */
    public function thePRShouldBeGreen()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS']['BUILD_RESULT'], 'GREEN', 'PR is expected to be green, but it wasn\'t');
    }

    /**
     * @Then /^the squad should be notified that the ci is green for the PR$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsGreenForThePullRequest()
    {
        Assert::assertTrue(
            $this->eventSpy->CIGreenEventDispatched(),
            'Expected CIGreenEvent to be dispatched, but was not found'
        );
        $this->chatClientSpy->assertReaction(
            $this->currentMessageIdentifier,
            NotifySquad::REACTION_CI_GREEN
        );
    }

    /**
     * @When /^the CI is red for the PR$/
     */
    public function theCIIsRedForThePullRequest()
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'akeneo/pim-community-dev';
        $CIStatusUpdate->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $CIStatusUpdate->status = 'RED';
        $CIStatusUpdate->buildLink = self::BUILD_LINK;
        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the PR should be red$/
     */
    public function thePRShouldBeRed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS']['BUILD_RESULT'], 'RED', 'PR is expected to be red, but it wasn\'t');
    }

    /**
     * @Given /^the squad should be notified that the ci is red for the PR$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsRedForThePullRequest()
    {
        Assert::assertTrue(
            $this->eventSpy->CIRedEventDispatched(),
            'Expected CIRedEvent to be dispatched, but was not found'
        );
        $this->chatClientSpy->assertReaction(
            $this->currentMessageIdentifier,
            NotifySquad::REACTION_CI_RED
        );
    }

    /**
     * @When /^the CI status changes for a PR belonging to an unsupported repository$/
     */
    public function theCIStatusChangesForAPRBelongingToAnUnsupportedRepository()
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'unsupported_repository';
        $CIStatusUpdate->PRIdentifier = 'unsupported_repository/1010';
        $CIStatusUpdate->status = 'GREEN';
        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the squad should not be not notified$/
     */
    public function theSquadShouldNotBeNotNotified()
    {
        Assert::assertFalse($this->eventSpy->hasEvents(), 'Expected to have no events, but some were found');
    }

    /**
     * @Given /^the author should be notified that the ci is green for the PR$/
     */
    public function theAuthorShouldBeNotifiedThatTheCiIsGreenForThePR()
    {
        Assert::assertTrue(
            $this->eventSpy->CIGreenEventDispatched(),
            'Expected CIGreenEvent to be dispatched, but was not found'
        );
        $this->chatClientSpy->assertReaction(
            $this->currentMessageIdentifier,
            NotifyAuthor::MESSAGE_CI_GREEN
        );
    }

    /**
     * @Given /^the author should be notified that the ci is red for the PR with the CI build link$/
     */
    public function theAuthorShouldBeNotifiedThatTheCiIsRedForThePR()
    {
        Assert::assertTrue(
            $this->eventSpy->CIRedEventDispatched(),
            'Expected CIRedEvent to be dispatched, but was not found'
        );
        $expectedMessage = str_replace(NotifyAuthor::PLACEHOLDER_BUILD_LINK,
            self::BUILD_LINK,
            NotifyAuthor::MESSAGE_CI_RED
        );
        $this->chatClientSpy->assertReaction(
            $this->currentMessageIdentifier,
            $expectedMessage
        );
    }

    /**
     * @Given /^a PR in review being green$/
     */
    public function aPRInReviewBeingGreen()
    {
        $this->currentPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $this->currentMessageIdentifier = MessageIdentifier::fromString('CHANNEL_ID@1');
        $PR = PR::create(
            $this->currentPRIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->green();
        $this->PRRepository->save($PR);
        $this->chatClientSpy->reset();
    }

    /**
     * @When /^the CI is being running for the PR$/
     */
    public function theCIIsBeingRunningForThePR()
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'akeneo/pim-community-dev';
        $CIStatusUpdate->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $CIStatusUpdate->status = 'PENDING';
        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the PR should be pending$/
     */
    public function thePRShouldBePending()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS']['BUILD_RESULT'], 'PENDING', 'PR is expected to be pending, but it wasn\'t');
    }

    /**
     * @Given /^the squad should be notified that the ci is pending for the PR$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsPendingForThePR()
    {
        Assert::assertTrue(
            $this->eventSpy->CIPendingEventDispatched(),
            'Expected CIPending to be dispatched, but was not found'
        );
        $this->chatClientSpy->assertReaction(
            $this->currentMessageIdentifier,
            NotifySquad::REACTION_CI_PENDING
        );
    }
}
