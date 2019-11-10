<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\ClosePR\ClosePR;
use Slub\Application\ClosePR\ClosePRHandler;
use Slub\Application\MergedPR\MergedPR;
use Slub\Application\MergedPR\MergedPRHandler;
use Slub\Application\Notify\NotifySquad;
use Slub\Domain\Entity\Channel\ChannelIdentifier;
use Slub\Domain\Entity\PR\AuthorIdentifier;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Entity\PR\Title;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class MergedPRContext extends FeatureContext
{
    /** @var MergedPRHandler */
    private $mergedPRHandler;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    /** @var MessageIdentifier */
    private $currentMessageIdentifier;

    /** @var ChatClientSpy */
    private $chatClientSpy;

    /** @var ClosePRHandler */
    private $closePRHandler;

    public function __construct(
        PRRepositoryInterface $PRRepository,
        MergedPRHandler $mergedPRHandler,
        ClosePRHandler $closePRHandler,
        EventsSpy $eventSpy,
        ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);
        $this->mergedPRHandler = $mergedPRHandler;
        $this->eventSpy = $eventSpy;
        $this->chatClientSpy = $chatClientSpy;
        $this->PRRepository = $PRRepository;
        $this->closePRHandler = $closePRHandler;
    }

    /**
     * @Given /^a PR in review having multiple comments and a CI result$/
     */
    public function aPullRequestInReview()
    {
        $this->currentPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $this->currentMessageIdentifier = MessageIdentifier::fromString('CHANNEL_ID@1');
        $this->PRRepository->save($this->PRWithGTMsAndGreen());
        $this->chatClientSpy->reset();
    }

    /**
     * @When /^the author merges the PR$/
     */
    public function theAuthorMergesThePR()
    {
        $mergedPR = new MergedPR();
        $mergedPR->repositoryIdentifier = 'akeneo/pim-community-dev';
        $mergedPR->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $this->currentPRIdentifier = PRIdentifier::fromString($mergedPR->PRIdentifier);
        $this->mergedPRHandler->handle($mergedPR);
    }

    /**
     * @Then /^the PR is merged$/
     */
    public function thePRIsMerged()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(true, $PR->normalize()['IS_MERGED'], 'Expects PR to be merged but was not');
    }

    /**
     * @Given /^the squad should be notified that the PR has been merged$/
     */
    public function theSquadShouldBeNotifiedThatThePRHasBeenMerged()
    {
        Assert::assertTrue($this->eventSpy->PRMergedDispatched(), 'Expects PRMerged event to be dispatched');
        $messageIdentifier = last($this->PRRepository->getBy($this->currentPRIdentifier)->messageIdentifiers());
        $this->chatClientSpy->assertOnlyReaction($messageIdentifier, NotifySquad::REACTION_PR_MERGED);
    }

    /**
     * @When /^the a PR belonging to an unsupported repository is merged$/
     */
    public function theAPRBelongingToAnUnsupportedRepositoryIsMerged()
    {
        $mergedPR = new MergedPR();
        $mergedPR->repositoryIdentifier = 'unsupported_repository';
        $mergedPR->PRIdentifier = '1010';
        $this->currentPRIdentifier = PRIdentifier::fromString($mergedPR->PRIdentifier);
        $this->mergedPRHandler->handle($mergedPR);
    }

    /**
     * @When /^the author closes the PR$/
     */
    public function theAuthorClosesThePR()
    {
        $closePR = new ClosePR();
        $closePR->repositoryIdentifier = 'akeneo/pim-community-dev';
        $closePR->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $this->currentPRIdentifier = PRIdentifier::fromString($closePR->PRIdentifier);
        $this->closePRHandler->handle($closePR);
    }

    /**
     * @Then /^the PR is closed$/
     */
    public function thePRIsClosed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertNotEmpty($PR->normalize()['CLOSED_AT'], 'Expects PR to be closed but not date was set on it');
    }

    private function PRWithGTMsAndGreen(): PR
    {
        $PR = PR::create($this->currentPRIdentifier,
            ChannelIdentifier::fromString('squad-raccoons'),
            $this->currentMessageIdentifier,
            AuthorIdentifier::fromString('sam'),
            Title::fromString('Add new feature')
        );
        $PR->GTM();
        $PR->GTM();
        $PR->green();

        return $PR;
    }

    /**
     * @Given /^the squad should be notified that the PR has been closed$/
     */
    public function theSquadShouldBeNotifiedThatThePRHasBeenClosed()
    {
        Assert::assertTrue($this->eventSpy->PRClosedDispatched(), 'Expects PRClosed event to be dispatched');
    }

    /**
     * @When /^the a PR belonging to an unsupported repository is closed$/
     */
    public function theAPRBelongingToAnUnsupportedRepositoryIsClosed()
    {
        $closePR = new ClosePR();
        $closePR->repositoryIdentifier = 'unsupported_repository';
        $closePR->PRIdentifier = '1010';
        $this->currentPRIdentifier = PRIdentifier::fromString($closePR->PRIdentifier);
        $this->closePRHandler->handle($closePR);
    }
}
