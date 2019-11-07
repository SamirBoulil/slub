<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\Notify\NotifySquad;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

class PutPRToReviewContext extends FeatureContext
{
    /** @var PutPRToReviewHandler */
    private $putPRToReviewHandler;

    /** @var string */
    private $currentPRIdentifier;

    /** @var string */
    private $currentRepositoryIdentifier;

    /** @var EventsSpy */
    private $eventSpy;

    /** @var ChatClientSpy */
    private $chatClientSpy;

    /** @var string[] */
    private $currentMessageIds = [];

    /** @var array */
    private $currentChannelIds = [];

    public function __construct(
        PRRepositoryInterface $PRRepository,
        PutPRToReviewHandler $putPRToReviewHandler,
        EventsSpy $eventSpy,
        ChatClientSpy $chatClientSpy
    ) {
        parent::__construct($PRRepository);

        $this->putPRToReviewHandler = $putPRToReviewHandler;
        $this->currentRepositoryIdentifier = '';
        $this->currentPRIdentifier = '';
        $this->eventSpy = $eventSpy;
        $this->chatClientSpy = $chatClientSpy;
    }

    /**
     * @When /^an author puts a PR to review in a channel$/
     */
    public function anAuthorPutsAPRToReview()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'squad-raccoons',
            '1234',
            'sam',
            'Add new feature'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    private function createPutPRToReviewCommand(
        string $repositoryIdentifier,
        string $PRIdentifier,
        string $channelIdentifier,
        string $messageId,
        string $authorIdentifier,
        string $title
    ): PutPRToReview {
        $this->currentRepositoryIdentifier = $repositoryIdentifier;
        $this->currentPRIdentifier = $PRIdentifier;
        $this->currentMessageIds[] = $messageId;
        $this->currentChannelIds[] = $channelIdentifier;

        $putPRToReview = new PutPRToReview();
        $putPRToReview->channelIdentifier = $channelIdentifier;
        $putPRToReview->repositoryIdentifier = $this->currentRepositoryIdentifier;
        $putPRToReview->PRIdentifier = $this->currentPRIdentifier;
        $putPRToReview->messageIdentifier = $messageId;
        $putPRToReview->authorIdentifier = $authorIdentifier;
        $putPRToReview->title = $title;
        $putPRToReview->CIStatus = 'PENDING';
        $putPRToReview->GTMCount = 0;
        $putPRToReview->notGTMCount = 0;
        $putPRToReview->comments = 0;
        $putPRToReview->isMerged = false;

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
            '1',
            'sam',
            'Add new feature'
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
            '1',
            'sam',
            'Add new feature'
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
            'sam',
            'Add new feature',
            0,
            0,
            0,
            'PENDING',
            false,
            $this->currentMessageIds,
            ['squad-raccoons']
        );
        Assert::assertTrue($this->eventSpy->PRPutToReviewDispatched());
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
     * @When /^an author puts a PR to review a second time in another channel$/
     */
    public function anAuthorPutsAPRToReviewASecondTime()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'general',
            '6666',
            'sam',
            'Add new feature'
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is updated with the new channel id and message id$/
     */
    public function thePRIsUpdatedWithTheNewMessageId()
    {
        $this->assertPR(
            $this->currentPRIdentifier,
            'sam',
            'Add new feature',
            0,
            0,
            0,
            'PENDING',
            false,
            $this->currentMessageIds,
            $this->currentChannelIds
        );
    }

    private function assertPR(
        string $prIdentifier,
        string $authorIdentifier,
        string $title,
        int $gtmCount,
        int $notGtmCount,
        int $commentsCount,
        string $ciStatus,
        bool $isMerged,
        array $messageIds,
        array $channelIds
    ): void {
        Assert::assertTrue($this->PRExists($prIdentifier));
        $pr = $this->PRRepository->getBy(PRIdentifier::create($prIdentifier));
        Assert::assertEquals($pr->normalize()['IDENTIFIER'], $prIdentifier);
        Assert::assertEquals($pr->normalize()['AUTHOR_IDENTIFIER'], $authorIdentifier);
        Assert::assertEquals($pr->normalize()['TITLE'], $title);
        Assert::assertEquals($pr->normalize()['GTMS'], $gtmCount);
        Assert::assertEquals($pr->normalize()['NOT_GTMS'], $notGtmCount);
        Assert::assertEquals($pr->normalize()['COMMENTS'], $commentsCount);
        Assert::assertEquals($pr->normalize()['CI_STATUS']['BUILD_RESULT'], $ciStatus);
        Assert::assertEmpty($pr->normalize()['CI_STATUS']['BUILD_LINK']);
        Assert::assertEquals($pr->normalize()['IS_MERGED'], $isMerged);
        Assert::assertEquals($pr->normalize()['MESSAGE_IDS'], $messageIds);
        Assert::assertEquals($pr->normalize()['CHANNEL_IDS'], $channelIds);
        Assert::assertNotEmpty($pr->normalize()['PUT_TO_REVIEW_AT']);
        Assert::assertEmpty($pr->normalize()['MERGED_AT']);
    }

    /**
     * @Given /^the squad should be notified that the PR has been successfully put to review$/
     */
    public function theSquadShouldBeNotifiedThatThePRHasBeenSuccessfullyInterpreted()
    {
        $this->chatClientSpy->assertReaction(MessageIdentifier::fromString(last($this->currentMessageIds)), NotifySquad::REACTION_CI_PENDING);
    }
}
