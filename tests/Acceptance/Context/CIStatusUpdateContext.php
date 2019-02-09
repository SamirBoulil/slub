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
        $CIStatusUpdate->repository = 'akeneo/pim-community-dev';
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
        Assert::assertTrue($PR->isGreen(), 'PR is expected to be green, but it wasn\'t');
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
}
