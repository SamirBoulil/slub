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
        $pr = new PutPRToReview('akeneo', 'pim-community-dev', '1111');
        $this->putPRToReviewHandler->handle($pr);
    }

    /**
     * @Then /^the PR is added to the list of followed PRs$/
     */
    public function thePRIsAddedToTheListOfFollowedPRs()
    {
        $this->assertPRRepositoryContains('akeneo', 'pim-community-dev', '1111');
    }

    private function assertPRRepositoryContains(string $organization, string $repository, string $externalId): void
    {
        $found = true;
        try {
            $this->prRepository->getBy(PRIdentifier::create($organization, $repository, $externalId));
        } catch (PRNotFoundException $notFoundException) {
            $found = false;
        }

        Assert::assertTrue($found, 'PR was not found');
    }
}
