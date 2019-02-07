<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\FileBased\Repository\FileBasedPRRepository;

class PutPRToReviewContext extends FeatureContext
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var string */
    private $currentPRIdentifier;

    /** @var string */
    private $currentRepositoryIdentifier;

    public function __construct(
        FileBasedPRRepository $PRRepository,
        PutPRToReviewHandler $putPRToReviewHandler
    ) {
        parent::__construct($PRRepository);

        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->currentRepositoryIdentifier = '';
        $this->currentPRIdentifier = '';
    }

    /**
     * @When /^an author puts a PR to review$/
     */
    public function anAuthorPutsAPRToReview()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'squad-raccoons'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @When /^an author puts a PR belonging to an unsupported repository to review$/
     */
    public function anAuthorPutsAPRBelongingToAnUnsupportedRepositoryToReview()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'unknown/unknown',
            'unknown/unknown/1111',
            'squad-raccoons'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @When /^an author puts a PR to review on an unsupported channel$/
     */
    public function anAuthorPutsAPRToReviewOnAnUnsupportedChannel()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'unsupported-channel'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is added to the list of followed PRs$/
     */
    public function thePRIsAddedToTheListOfFollowedPRs()
    {
        Assert::assertTrue($this->PRExists($this->currentPRIdentifier));
    }

    /**
     * @Then /^the PR is not added to the list of followed PRs$/
     */
    public function thePRIsNotAddedToTheListOfFollowedPRs()
    {
        Assert::assertFalse($this->PRExists($this->currentPRIdentifier));
    }

    private function PRExists(string $PRIdentifier): bool
    {
        $found = true;
        try {
            $this->PRRepository->getBy(PRIdentifier::create($PRIdentifier));
        } catch (PRNotFoundException $notFoundException) {
            $found = false;
        }

        return $found;
    }

    private function createPutPRToReviewCommand(
        string $repositoryIdentifier,
        string $PRIdentifier,
        string $channelIdentifier
    ): PutPRToReview {
        $this->currentRepositoryIdentifier = $repositoryIdentifier;
        $this->currentPRIdentifier = $PRIdentifier;
        $putPRToReview = new PutPRToReview(
            $channelIdentifier,
            $this->currentRepositoryIdentifier,
            $this->currentPRIdentifier
        );

        return $putPRToReview;
    }
}
