<?php

declare(strict_types=1);

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\MergedPR\MergedPR;
use Slub\Application\MergedPR\MergedPRHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Infrastructure\Persistence\FileBased\Repository\SqlPRRepository;
use Tests\Acceptance\helpers\EventsSpy;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 */
class MergedPRContext extends FeatureContext
{
    /** @var MergedPRHandler */
    private $mergedPRHandler;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var PRIdentifier */
    private $currentPRIdentifier;

    public function __construct(
        SqlPRRepository $repository,
        MergedPRHandler $mergedPRHandler,
        EventsSpy $eventSpy
    ) {
        parent::__construct($repository);
        $this->mergedPRHandler = $mergedPRHandler;
        $this->eventSpy = $eventSpy;
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
}
