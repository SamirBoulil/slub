<?php

namespace Tests\Acceptance\Context;

use PHPUnit\Framework\Assert;
use Slub\Application\Notify\NotifySquad;
use Slub\Application\PutPRToReview\PutPRToReview;
use Slub\Application\PutPRToReview\PutPRToReviewHandler;
use Slub\Domain\Entity\PR\MessageIdentifier;
use Slub\Domain\Entity\PR\PR;
use Slub\Domain\Entity\PR\PRIdentifier;
use Slub\Domain\Repository\PRNotFoundException;
use Slub\Domain\Repository\PRRepositoryInterface;
use Tests\Acceptance\helpers\ChatClientSpy;
use Tests\Acceptance\helpers\EventsSpy;

class PutPRToReviewContext extends FeatureContext
{
    private string $currentPRIdentifier;

    private string $currentRepositoryIdentifier;

    /** @var string[] */
    private array $currentMessageIds = [];

    private array $currentChannelIds = [];

    private array $currentWorkspaceIds = [];

    public function __construct(
        PRRepositoryInterface $PRRepository,
        private PutPRToReviewHandler $putPRToReviewHandler,
        private EventsSpy $eventSpy,
        private ChatClientSpy $chatClientSpy,
        private string $prSizeLimit
    ) {
        parent::__construct($PRRepository);
        $this->currentRepositoryIdentifier = '';
        $this->currentPRIdentifier = '';
    }

    /**
     * @When /^an author puts a PR to review in a channel$/
     */
    public function anAuthorPutsAPRToReview(): void
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'squad-raccoons',
            'akeneo',
            '1234',
            'Add new feature',
            'sam',
            false,
            100,
            100
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is added to the list of followed PRs$/
     */
    public function thePRIsAddedToTheListOfFollowedPRs(): void
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
            ['squad-raccoons'],
            ['akeneo']
        );
        Assert::assertTrue($this->eventSpy->PRPutToReviewDispatched());
    }

    private function PRExists(string $PRIdentifier): bool
    {
        $found = true;
        try {
            $this->PRRepository->getBy(PRIdentifier::create($PRIdentifier));
        } catch (PRNotFoundException) {
            $found = false;
        }

        return $found;
    }

    /**
     * @When /^an author puts a PR to review a second time in another channel$/
     */
    public function anAuthorPutsAPRToReviewASecondTime(): void
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'general',
            'akeneo',
            '6666',
            'Add new feature',
            'sam',
            false,
            100,
            100
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is updated with the new channel id and message id$/
     */
    public function thePRIsUpdatedWithTheNewMessageId(): void
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
            $this->currentChannelIds,
            ['akeneo']
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
        array $channelIds,
        array $workspaceIds
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
        Assert::assertEquals($pr->normalize()['WORKSPACE_IDS'], $workspaceIds);
        Assert::assertNotEmpty($pr->normalize()['PUT_TO_REVIEW_AT']);
        Assert::assertEmpty($pr->normalize()['CLOSED_AT']);
    }

    /**
     * @Given /^the squad should be notified that the PR has been successfully put to review$/
     */
    public function theSquadShouldBeNotifiedThatThePRHasBeenSuccessfullyInterpreted(): void
    {
        $this->chatClientSpy->assertReaction(MessageIdentifier::fromString(end($this->currentMessageIds)), NotifySquad::REACTION_CI_PENDING);
    }

    /**
     * @Given /^an author closes a PR that was in review in a channel$/
     */
    public function anAuthorClosesAPRThatWasInReviewInAChannel(): void
    {
        $putToReviewTimestamp = (string) (new \DateTime('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d day', 2))
            ->getTimestamp();
        $closedAtTimestamp = (string) (new \DateTime('now', new \DateTimeZone('UTC')))
            ->modify(sprintf('-%d day', 1))
            ->getTimestamp();

        $PR = PR::fromNormalized([
                'IDENTIFIER' => 'akeneo/pim-community-dev/1111',
                'AUTHOR_IDENTIFIER' => 'sam',
                'TITLE' => 'Add new feature',
                'GTMS' => 0,
                'NOT_GTMS' => 0,
                'COMMENTS' => 0,
                'CI_STATUS' => ['BUILD_RESULT' => 'PENDING', 'BUILD_LINK' => ''],
                'IS_MERGED' => true,
                'MESSAGE_IDS' => [],
                'CHANNEL_IDS' => ['squad-raccoons'],
                'WORKSPACE_IDS' => ['akeneo'],
                'PUT_TO_REVIEW_AT' => $putToReviewTimestamp,
                'CLOSED_AT' => $closedAtTimestamp,
                 'IS_TOO_LARGE' => false,
            ]
        );
        $this->PRRepository->save($PR);
    }

    /**
     * @When /^an author reopens the PR and puts it to review$/
     */
    public function anAuthorReopensThePRAndPutsItToReview(): void
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'squad-raccoons',
            'akeneo',
            '1234',
            'Add new feature',
            'sam',
            false,
            100,
            100
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the PR is reopened with the new channel id and message id$/
     */
    public function thePRIsReopenedWithTheNewChannelIdAndMessageId(): void
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
            $this->currentChannelIds,
            $this->currentWorkspaceIds
        );
    }

    /**
     * @Given /^an author puts a PR to review that is too large$/
     */
    public function aPRInReviewThatIsAlreadyTooLarge()
    {
        $putPRToReview = $this->createPutPRToReviewCommand(
            'akeneo/pim-community-dev',
            'akeneo/pim-community-dev/1111',
            'squad-raccoons',
            'akeneo',
            '1234',
            'Add new feature',
            'sam',
            false,
            10000,
            10000
        );
        $this->putPRToReviewHandler->handle($putPRToReview);
    }

    /**
     * @Then /^the author should be notified that the PR is too large$/
     */
    public function theAuthorShouldBeNotifiedThatThePRIsTooLarge()
    {
        Assert::assertTrue($this->eventSpy->PRTooLargeDispatched(), 'Expect a PR Too large event to be dispatched');
        $warningMessage = sprintf(
            ':warning: <https://github.com/akeneo/pim-community-dev/pull/1111|Your PR> might be hard to review (> %s lines).',
            $this->prSizeLimit
        );
        $this->chatClientSpy->assertRepliedWithOneOf([$warningMessage]);
    }

    private function createPutPRToReviewCommand(
        string $repositoryIdentifier,
        string $PRIdentifier,
        string $channelIdentifier,
        string $workspaceIdentifier,
        string $messageId,
        string $title,
        string $authorIdentifier,
        bool $isClosed,
        int $additions,
        int $deletions
    ): PutPRToReview {
        $this->currentRepositoryIdentifier = $repositoryIdentifier;
        $this->currentPRIdentifier = $PRIdentifier;
        $this->currentMessageIds[] = $messageId;
        $this->currentChannelIds[] = $channelIdentifier;
        $this->currentWorkspaceIds[] = $workspaceIdentifier;

        $putPRToReview = new PutPRToReview();
        $putPRToReview->channelIdentifier = $channelIdentifier;
        $putPRToReview->workspaceIdentifier = $workspaceIdentifier;
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
        $putPRToReview->isClosed = $isClosed;
        $putPRToReview->additions = $additions;
        $putPRToReview->deletions = $deletions;

        return $putPRToReview;
    }
}
