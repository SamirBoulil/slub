<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Domain\Entity\PR\MessageId;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;
use Tests\Acceptance\helpers\EventsSpy;

class ReviewContext extends FeatureContext
{
    /** @var NewReviewHandler */
    private $ReviewHandler;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    public function __construct(
        FileBasedPRRepository $PRRepository,
        NewReviewHandler $reviewHandler,
        EventsSpy $eventSpy
    ) {
        parent::__construct($PRRepository);

        $this->ReviewHandler = $reviewHandler;
        $this->eventSpy = $eventSpy;
    }

    /**
     * @Given /^a PR in review$/
     */
    public function aPullRequestInReview()
    {
        $this->currentPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev/1010');
        $this->PRRepository->save(PR::create($this->currentPRIdentifier, MessageId::fromString('1')));
    }

    /**
     * @When /^the PR is GTMed$/
     */
    public function thePullRequestIsGTMed()
    {
        $gtm = new NewReview();
        $gtm->repositoryIdentifier = 'akeneo/pim-community-dev';
        $gtm->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $gtm->isGTM = true;
        $this->ReviewHandler->handle($gtm);
    }

    /**
     * @Then /^the PR should be GTMed$/
     */
    public function thePullRequestShouldBeGTMed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['GTM']);
    }

    /**
     * @Then /^the squad should be notified that the PR has one more GTM$/
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
     * @When /^the PR is NOT GTMED$/
     */
    public function thePullRequestIsNOTGTMED()
    {
        $notGTM = new NewReview();
        $notGTM->repositoryIdentifier = 'akeneo/pim-community-dev';
        $notGTM->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $notGTM->isGTM = false;
        $this->ReviewHandler->handle($notGTM);
    }

    /**
     * @Then /^the PR should be NOT GTMed$/
     */
    public function thePullRequestShouldBeNOTGTMed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals(1, $PR->normalize()['NOT_GTM']);
    }

    /**
     * @Then /^the squad should be notified that the PR has one more NOT GTM$/
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
     * @When /^a PR is reviewed on an unsupported repository$/
     */
    public function aPullRequestIsReviewedOnAnUnsupportedRepository()
    {
        $this->currentPRIdentifier = PRIdentifier::fromString('1010');

        $notGTM = new NewReview();
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
