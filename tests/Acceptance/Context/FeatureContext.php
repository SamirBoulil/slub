<?php

namespace Tests\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use PHPUnit\Framework\Assert;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Slub\Infrastructure\Common\SlubApplicationContainer;

class FeatureContext implements Context
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var PRRepositoryInterface */
    private $prRepository;

    public function __construct()
    {
        $slub = new SlubApplicationContainer();
        $this->putPRToReviewHandler = $slub->get(PutPRToReviewHandler::class);
        $this->prRepository = $slub->get(PRRepositoryInterface::class);
    }

    /**
     * @When /^an author puts a PR to review$/
     */
    public function anAuthorPutsAPRToReview()
    {
        $pr = new PutPRToReview('akeneo/pim-community-dev', '1111');
        $this->putPRToReviewHandler->handle($pr);
    }

    /**
     * @When /^an author puts a PR belonging to an unsupported repository  to review$/
     */
    public function anAuthorPutsAPRBelongingToAnUnsupportedRepositoryToReview()
    {
        $pr = new PutPRToReview('akeneo/pim-community-dev', '1111');
        $exception = null;
        try {
            $this->putPRToReviewHandler->handle($pr);
        } catch (\Exception $e) {
            $exception = $e;
        }
    }

    /**
     * @Then /^the PR is added to the list of followed PRs$/
     */
    public function thePRIsAddedToTheListOfFollowedPRs()
    {
        Assert::assertTrue($this->prExists('akeneo/pim-community-dev', '1111'));
    }

    /**
     * @Then /^the PR is not added to the list of followed PRs$/
     */
    public function thePRIsNotAddedToTheListOfFollowedPRs()
    {
        Assert::assertFalse($this->prExists('akeneo/unknown', '1111'));
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
