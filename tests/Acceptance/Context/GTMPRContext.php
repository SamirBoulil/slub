<?php

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Slub\Application\GTMPR\GTMPR;
use Slub\Application\GTMPR\GTMPRHandler;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Common\SlubApplicationContainer;
use Tests\Acceptance\helpers\PRGTMedSubscriberSpy;

class GTMPRContext implements Context
{
    /** @var PRRepositoryInterface $PRRepository */
    private $PRRepository;

    /** @var GTMPRHandler $GTMPRHandler */
    private $GTMPRHandler;

    /** @var PRGTMedSubscriberSpy $PRGTMedSubscriberSpy */
    private $PRGTMedSubscriberSpy;

    /** @var PRIdentifier $currentPRIdentifier */
    private $currentPRIdentifier;

    public function __construct()
    {
        $slub = SlubApplicationContainer::buildForTest();
        $this->PRRepository = $slub->get(PRRepositoryInterface::class);
        $this->GTMPRHandler = $slub->get(GTMPRHandler::class);
        $this->PRGTMedSubscriberSpy = $slub->get(PRGTMedSubscriberSpy::class);
    }

    /**
     * @Given /^a pull request in review$/
     */
    public function aPullRequestInReview()
    {
        $this->currentPRIdentifier = PRIdentifier::create('akeneo/pim-community-dev', '1010');
        $this->PRRepository->save(PR::create($this->currentPRIdentifier));
    }

    /**
     * @When /^the pull request is GTMed$/
     */
    public function thePullRequestIsGTMed()
    {
        $command = new GTMPR();
        $command->repository = 'akeneo/pim-community-dev';
        $command->prIdentifier = '1010';
        $this->GTMPRHandler->handle($command);
    }

    /**
     * @Then /^the squad should be notified that the pull request has one more GTM$/
     */
    public function theSquadShouldBeNotifiedThatThePullRequestHasOneMoreGTM()
    {
        Assert::assertNotNull($this->currentPRIdentifier, 'The PR identifier was not created');
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        $GTM = $PR->normalize()['GTM'];
        Assert::assertEquals(1, $GTM, sprintf('The PR has %d GTMS, expected %d', $GTM, 1));
        Assert::assertTrue($this->PRGTMedSubscriberSpy->PRhasBeenGMTed());
    }
}
