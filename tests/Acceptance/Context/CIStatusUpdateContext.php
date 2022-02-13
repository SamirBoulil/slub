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

    private PRIdentifier $currentPRIdentifier;

    private MessageIdentifier $currentMessageIdentifier;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private CIStatusUpdateHandler $CIStatusUpdateHandler,
        private EventsSpy $eventSpy,
        private ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
        $this->PRRepository = $PRRepository;
    }

    /**
     * @Given /^a PR in review waiting for the CI results$/
     */
    public function aPRInReviewWaitingForTheCIResults(): void
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
    public function theCIIsGreenForThePullRequest(): void
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
    public function thePRShouldBeGreen(): void
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS']['BUILD_RESULT'], 'GREEN', 'PR is expected to be green, but it wasn\'t');
    }

    /**
     * @Then /^the squad should be notified that the ci is green for the PR$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsGreenForThePullRequest(): void
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
    public function theCIIsRedForThePullRequest(): void
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
    public function thePRShouldBeRed(): void
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS']['BUILD_RESULT'], 'RED', 'PR is expected to be red, but it wasn\'t');
    }

    /**
     * @Given /^the squad should be notified that the ci is red for the PR$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsRedForThePullRequest(): void
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
     * @Given /^the author should be notified that the ci is green for the PR$/
     */
    public function theAuthorShouldBeNotifiedThatTheCiIsGreenForThePR(): void
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
    public function theAuthorShouldBeNotifiedThatTheCiIsRedForThePR(): void
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
    public function aPRInReviewBeingGreen(): void
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
    public function theCIIsBeingRunningForThePR(): void
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
    public function thePRShouldBePending(): void
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS']['BUILD_RESULT'], 'PENDING', 'PR is expected to be pending, but it wasn\'t');
    }

    /**
     * @Given /^the squad should be notified that the ci is pending for the PR$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsPendingForThePR(): void
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
