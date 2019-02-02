<?php

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Common\SlubApplicationContainer;

class PutPRToReviewContext implements Context
{
    /** @var string */
    private $currentRepository;

    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var PRRepositoryInterface */
    private $prRepository;

    public function __construct()
    {
        $slub = SlubApplicationContainer::buildForTest();
        $this->putPRToReviewHandler = $slub->get(PutPRToReviewHandler::class);
        $this->prRepository = $slub->get(PRRepositoryInterface::class);
        $this->currentRepository = '';
    }

    /**
     * @When /^an author puts a PR to review$/
     */
    public function anAuthorPutsAPRToReview()
    {
        $this->currentRepository ='akeneo/pim-community-dev';
        $putToReview = new PutPRToReview('squad-raccoons', $this->currentRepository, '1111');
        $this->putPRToReviewHandler->handle($putToReview);
    }

    /**
     * @When /^an author puts a PR belonging to an unsupported repository to review$/
     */
    public function anAuthorPutsAPRBelongingToAnUnsupportedRepositoryToReview()
    {
        $this->currentRepository ='unknown/unknown';
        $putToReview = new PutPRToReview('squad-raccoons', $this->currentRepository, '1111');
        $this->putPRToReviewHandler->handle($putToReview);
    }

    /**
     * @When /^an author puts a PR to review on an unsupported channel$/
     */
    public function anAuthorPutsAPRToReviewOnAnUnsupportedChannel()
    {
        $this->currentRepository = 'akeneo/pim-community-dev';
        $putToReview = new PutPRToReview('unsupported-channel', $this->currentRepository, '1111');
        $this->putPRToReviewHandler->handle($putToReview);
    }

    /**
     * @Then /^the PR is added to the list of followed PRs$/
     */
    public function thePRIsAddedToTheListOfFollowedPRs()
    {
        Assert::assertTrue($this->prExists($this->currentRepository, '1111'));
    }

    /**
     * @Then /^the PR is not added to the list of followed PRs$/
     */
    public function thePRIsNotAddedToTheListOfFollowedPRs()
    {
        Assert::assertFalse($this->prExists($this->currentRepository, '1111'));
    }

    private function prExists(string $repository, string $externalId): bool
    {
        $found = true;
        try {
            $this->prRepository->getBy(PRIdentifier::create($repository, $externalId));
        } catch (PRNotFoundException $notFoundException) {
            $found = false;
        }

        return $found;
    }
}
