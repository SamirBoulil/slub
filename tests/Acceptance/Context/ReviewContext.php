<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\Review\Review;
use Slub\Application\Review\ReviewHandler;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;
use Tests\Acceptance\helpers\PRGTMedSubscriberSpy;
use Tests\Acceptance\helpers\PRNotGTMedSubscriberSpy;

class ReviewContext extends FeatureContext
{
    /** @var ReviewHandler */
    private $ReviewHandler;

    /** @var PRGTMedSubscriberSpy */
    private $PRGTMedSubscriberSpy;

    /** @var PRNotGTMedSubscriberSpy */
    private $PRNotGTMedSubscriberSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    public function __construct(
        FileBasedPRRepository $PRRepository,
        ReviewHandler $reviewHandler,
        PRGTMedSubscriberSpy $PRGTMedNotify,
        PRNotGTMedSubscriberSpy $PRNotGTMedNotify
    ) {
        parent::__construct($PRRepository);

        $this->ReviewHandler = $reviewHandler;
        $this->PRGTMedSubscriberSpy = $PRGTMedNotify;
        $this->PRNotGTMedSubscriberSpy = $PRNotGTMedNotify;
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
        Assert::assertTrue($this->PRGTMedSubscriberSpy->PRhasBeenGMTed());
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
        Assert::assertTrue($this->PRNotGTMedSubscriberSpy->PRhasNotBeenGMTed());
    }
}
