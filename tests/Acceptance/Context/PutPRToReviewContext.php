<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Infrastructure\Persistence\FileBased\Repository\SqlPRRepository;

class PutPRToReviewContext extends FeatureContext
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var string */
    private $currentPRIdentifier;

    /** @var string */
    private $currentRepositoryIdentifier;

    /** @var string[] */
    private $currentMessageIds = [];

    public function __construct(
        SqlPRRepository $PRRepository,
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
            'squad-raccoons',
            '1234'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    private function createPutPRToReviewCommand(
        string $repositoryIdentifier,
        string $PRIdentifier,
        string $channelIdentifier,
        string $messageId
    ): PutPRToReview {
        $this->currentRepositoryIdentifier = $repositoryIdentifier;
        $this->currentPRIdentifier = $PRIdentifier;
        $this->currentMessageIds[] = $messageId;

        $putPRToReview = new PutPRToReview();
        $putPRToReview->channelIdentifier = $channelIdentifier;
        $putPRToReview->repositoryIdentifier = $this->currentRepositoryIdentifier;
        $putPRToReview->PRIdentifier = $this->currentPRIdentifier;
        $putPRToReview->messageId = $messageId;

        return $putPRToReview;
    }

    /**
     * @When /^an author puts a PR belonging to an unsupported repository to review$/
     */
    public function anAuthorPutsAPRBelongingToAnUnsupportedRepositoryToReview()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'unknown/unknown',
            'unknown/unknown/1111',
            'squad-raccoons',
            '1'
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
            'unsupported-channel',
            '1'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is added to the list of followed PRs$/
     */
    public function thePRIsAddedToTheListOfFollowedPRs()
    {
        $this->assertPR(
            $this->currentPRIdentifier,
            0,
            0,
            'PENDING',
            false,
            $this->currentMessageIds
        );
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

    /**
     * @Then /^the PR is not added to the list of followed PRs$/
     */
    public function thePRIsNotAddedToTheListOfFollowedPRs()
    {
        Assert::assertFalse($this->PRExists($this->currentPRIdentifier));
    }

    /**
     * @When /^an author puts a PR to review a second time$/
     */
    public function anAuthorPutsAPRToReviewASecondTime()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'squad-raccoons',
            '6666'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is updated with the new message id$/
     */
    public function thePRIsUpdatedWithTheNewMessageId()
    {
        $this->assertPR(
            $this->currentPRIdentifier,
            0,
            0,
            'PENDING',
            false,
            $this->currentMessageIds
        );
    }

    private function assertPR(
        string $prIdentifier,
        int $gtmCount,
        int $notGtmCount,
        $ciStatus,
        $isMerged,
        $messageIds
    ): void {
        Assert::assertTrue($this->PRExists($prIdentifier));
        $pr = $this->PRRepository->getBy(PRIdentifier::create($prIdentifier));
        Assert::assertEquals([
            'IDENTIFIER'  => $prIdentifier,
            'GTMS'         => $gtmCount,
            'NOT_GTMS'     => $notGtmCount,
            'CI_STATUS'   => $ciStatus,
            'IS_MERGED'   => $isMerged,
            'MESSAGE_IDS' => $messageIds
        ], $pr->normalize());
    }
}
