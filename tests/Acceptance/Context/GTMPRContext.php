<?php

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Slub\Application\GTMPR\Review;
use Slub\Application\GTMPR\ReviewHandler;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Common\SlubApplicationContainer;
use Tests\Acceptance\helpers\PRGTMedSubscriberSpy;
use Tests\Acceptance\helpers\PRNotGTMedSubscriberSpy;

class GTMPRContext implements Context
{
    /** @var PRRepositoryInterface */
    private $PRRepository;

    /** @var ReviewHandler */
    private $ReviewHandler;

    /** @var PRGTMedSubscriberSpy */
    private $PRGTMedSubscriberSpy;

    /** @var PRNotGTMedSubscriberSpy */
    private $PRNotGTMedSubscriberSpy;

    /** @var PRIdentifier $currentPRIdentifier */
    private $currentPRIdentifier;

    public function __construct()
    {
        $slub = SlubApplicationContainer::buildForTest();
        $this->PRRepository = $slub->get(PRRepositoryInterface::class);
        $this->ReviewHandler = $slub->get(ReviewHandler::class);
        $this->PRGTMedSubscriberSpy = $slub->get(PRGTMedSubscriberSpy::class);
        $this->PRNotGTMedSubscriberSpy = $slub->get(PRNotGTMedSubscriberSpy::class);
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
