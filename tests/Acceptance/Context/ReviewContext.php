<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\Review\Review;
use Slub\Application\Review\ReviewHandler;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;
use Tests\Acceptance\helpers\EventsSpy;

class ReviewContext extends FeatureContext
{
    /** @var ReviewHandler */
    private $ReviewHandler;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    public function __construct(
        FileBasedPRRepository $PRRepository,
        ReviewHandler $reviewHandler,
        EventsSpy $eventSpy
    ) {
        parent::__construct($PRRepository);

        $this->ReviewHandler = $reviewHandler;
        $this->eventSpy = $eventSpy;
    }

    /**
     * @Given /^a pull request in review$/
     */
    public function aPullRequestInReview()
    {
        $this->currentPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $this->PRRepository->save(PR::create($this->currentPRIdentifier));
    }

    /**
     * @When /^the pull request is GTMed$/
     */
    public function thePullRequestIsGTMed()
    {
        $gtm = new Review();
        $gtm->repositoryIdentifier = 'akeneo/pim-community-dev';
        $gtm->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $gtm->isGTM = true;
        $this->ReviewHandler->handle($gtm);
    }

    /**
     * @Then /^the squad should be notified that the pull request has one more GTM$/
     */
    public function theSquadShouldBeNotifiedThatThePullRequestHasOneMoreGTM()
    {
        Assert::assertNotNull($this->currentPRIdentifier, 'The PR identifier was not created');
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        $GTMCount = $PR->normalize()['GTM'];
        Assert::assertEquals(1, $GTMCount, sprintf('The PR has %d GTMS, expected %d', $GTMCount, 1));
        Assert::assertTrue($this->eventSpy->PRGMTedDispatched());
    }

    /**
     * @When /^the pull request is NOT GTMED$/
     */
    public function thePullRequestIsNOTGTMED()
    {
        $notGTM = new Review();
        $notGTM->repositoryIdentifier = 'akeneo/pim-community-dev';
        $notGTM->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $notGTM->isGTM = false;
        $this->ReviewHandler->handle($notGTM);
    }

    /**
     * @Then /^the squad should be notified that the pull request has one more NOT GTM$/
     */
    public function theSquadShouldBeNotifiedThatThePullRequestHasOneMoreNOTGTM()
    {
        Assert::assertNotNull($this->currentPRIdentifier, 'The PR identifier was not created');
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        $notGTMCount = $PR->normalize()['NOT_GTM'];
        Assert::assertEquals(1, $notGTMCount, sprintf('The PR has %d NOT GTMS, expected %d', $notGTMCount, 1));
        Assert::assertTrue($this->eventSpy->PRNotGMTedDispatched());
    }

    /**
     * @When /^a pull request is reviewed on an unsupported repository$/
     */
    public function aPullRequestIsReviewedOnAnUnsupportedRepository()
    {
        $this->currentPRIdentifier = PRIdentifier::fromString('1010');

        $notGTM = new Review();
        $notGTM->repositoryIdentifier = 'unsupported_repository';
        $notGTM->PRIdentifier = '1010';
        $notGTM->isGTM = false;

        $this->ReviewHandler->handle($notGTM);
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
}
