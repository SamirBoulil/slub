<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use Behat\Behat\Tester\Exception\PendingException;
use PHPUnit\Framework\Assert;
use Slub\Application\CIStatusUpdate\CIStatusUpdate;
use Slub\Application\CIStatusUpdate\CIStatusUpdateHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;
use Tests\Acceptance\helpers\EventsSpy;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class CIStatusUpdateContext extends FeatureContext
{
    /** @var CIStatusUpdateHandler */
    private $CIStatusUpdateHandler;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    public function __construct(
        FileBasedPRRepository $repository,
        CIStatusUpdateHandler $CIStatusUpdateHandler,
        EventsSpy $eventSpy
    ) {
        parent::__construct($repository);
        $this->CIStatusUpdateHandler = $CIStatusUpdateHandler;
        $this->eventSpy = $eventSpy;
    }

    /**
     * @When /^the CI is green for the pull request$/
     */
    public function theCIIsGreenForThePullRequest()
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'akeneo/pim-community-dev';
        $CIStatusUpdate->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $CIStatusUpdate->isGreen = true;
        $this->currentPRIdentifier = PRIdentifier::fromString($CIStatusUpdate->PRIdentifier);

        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the PR should be green$/
     */
    public function thePRShouldBeGreen()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS'], 'GREEN', 'PR is expected to be green, but it wasn\'t');
    }

    /**
     * @Then /^the squad should be notified that the ci is green for the pull request$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsGreenForThePullRequest()
    {
        Assert::assertTrue(
            $this->eventSpy->CIGreenEventDispatched(),
            'Expected CIGreenEvent to be dispatched, but was not found'
        );
    }

    /**
     * @When /^the CI is red for the pull request$/
     */
    public function theCIIsRedForThePullRequest()
    {
        $CIStatusUpdate = new CIStatusUpdate();
        $CIStatusUpdate->repositoryIdentifier = 'akeneo/pim-community-dev';
        $CIStatusUpdate->PRIdentifier = 'akeneo/pim-community-dev/1010';
        $CIStatusUpdate->isGreen = false;
        $this->currentPRIdentifier = PRIdentifier::fromString($CIStatusUpdate->PRIdentifier);

        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the PR should be red$/
     */
    public function thePRShouldBeRed()
    {
        $PR = $this->PRRepository->getBy($this->currentPRIdentifier);
        Assert::assertEquals($PR->normalize()['CI_STATUS'], 'RED', 'PR is expected to be red, but it wasn\'t');
    }

    /**
     * @Given /^the squad should be notified that the ci is red for the pull request$/
     */
    public function theSquadShouldBeNotifiedThatTheCiIsRedForThePullRequest()
    {
        Assert::assertTrue(
            $this->eventSpy->CIRedEventDispatched(),
            'Expected CIGreenEvent to be dispatched, but was not found'
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
        $CIStatusUpdate->isGreen = true;
        $this->currentPRIdentifier = PRIdentifier::fromString($CIStatusUpdate->PRIdentifier);

        $this->CIStatusUpdateHandler->handle($CIStatusUpdate);
    }

    /**
     * @Then /^the squad should not be not notified$/
     */
    public function theSquadShouldNotBeNotNotified()
    {
        Assert::assertFalse($this->eventSpy->hasEvents(), 'Expected to have no events, but some were found');
    }
}
