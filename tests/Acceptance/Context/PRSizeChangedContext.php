<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\ChangePRSize\ChangePRSize;
use Slub\Application\ChangePRSize\ChangePRSizeHandler;
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

class PRSizeChangedContext extends FeatureContext
{
    private PRIdentifier $currentPRIdentifier;
    private MessageIdentifier $currentMessageIdentifier;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private ChangePRSizeHandler $changePRSizeHandler,
        private EventsSpy $eventSpy,
        private ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
    }

    /**
     * @Given /^a PR in review that has an acceptable size$/
     */
    public function aPRInReviewThatHasAnAcceptableSize()
    {
        $this->currentPRIdentifier = PRIdentifier::fromString('akeneo/pim-community-dev/1234');
        $this->currentMessageIdentifier = MessageIdentifier::fromString('message-id');
        $acceptableSizedPR = PR::create(
            $this->currentPRIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $this->PRRepository->save($acceptableSizedPR);
    }

    /**
     * @When /^the author updates the PR with (.*) additions and (.*) deletions$/
     */
    public function theAuthorUpdatesThePRTooThePointThatItBecomesTooLargeWithAnd(int $additions, int $deletions)
    {
        $changePRSize = new ChangePRSize();
        $changePRSize->PRIdentifier = $this->currentPRIdentifier->stringValue();
        $changePRSize->deletions = $additions;
        $changePRSize->additions = $deletions;

        $this->changePRSizeHandler->handle($changePRSize);
    }

    /**
     * @Then /^the author should be notified that the PR has become too large$/
     */
    public function theAuthorShouldBeNotifiedThatThePRIsTooLarge()
    {
        Assert::assertTrue($this->eventSpy->PRTooLargeDispatched(), 'Expect a PR Too large event to be dispatched');
        $warningMessage = ':warning: <https://github.com/akeneo/pim-community-dev/pull/1234|Your PR> might be hard to review (> 800 lines).';
        $this->chatClientSpy->assertRepliedWithOneOf([$warningMessage]);
    }

    /**
     * @Then /^the author should not be notified that the PR size has changed$/
     */
    public function theAuthorShouldNotBeNotifiedThatThePRSizeHasChanged()
    {
        Assert::assertFalse($this->eventSpy->PRTooLargeDispatched(), 'Expect a PR Too large event to NOT be dispatched');
    }

    /**
     * @Given /^a large PR in review$/
     */
    public function aLargePRInReview()
    {
        $this->currentPRIdentifier = PRIdentifier::fromString('akeneo/pim-community-dev/1234');
        $this->currentMessageIdentifier = MessageIdentifier::fromString('message-id');
        $largePR = PR::create(
            $this->currentPRIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            WorkspaceIdentifier::fromString('akeneo'),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature'),
            0,
            0,
            0,
            'PENDING',
            false,
            true
        );
        $this->PRRepository->save($largePR);
        $this->eventSpy->reset();
    }
}
