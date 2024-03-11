<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PullRequestReviewEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PullRequestReviewEventHandlerTest extends TestCase
{
    use ProphecyTrait;
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /**
     * @sut
     */
    private PullRequestReviewEventHandler $pullRequestReviewHandler;

    private NewReviewHandler|ObjectProphecy $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(NewReviewHandler::class);
        $this->pullRequestReviewHandler = new PullRequestReviewEventHandler($this->handler->reveal());
    }

    /**
     * @test
     */
    public function it_only_listens_to_pull_request_review_events(): void
    {
        self::assertTrue($this->pullRequestReviewHandler->supports('pull_request_review'));
        self::assertFalse($this->pullRequestReviewHandler->supports('unsupported_event'));
    }

    /**
     * @test
     * @dataProvider reviewStates
     */
    public function it_listens_to_accepted_PR(string $reviewState, string $expectedReviewStatus): void
    {
        $newReview = [
            'action' => 'submitted',
            'pull_request' => ['number' => self::PR_NUMBER, 'user' => ['id' => 1, 'login' => 'lucie']],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
            'review' => ['state' => $reviewState, 'user' => ['id' => 2, 'login' => 'samir']],
        ];

        $this->handler->handle(
            Argument::that(
                fn (NewReview $newReview) => self::PR_IDENTIFIER === $newReview->PRIdentifier
                    && self::REPOSITORY_IDENTIFIER === $newReview->repositoryIdentifier
                    && $expectedReviewStatus === $newReview->reviewStatus
            )
        )->shouldBeCalled();

        $this->pullRequestReviewHandler->handle($newReview);
    }

    public function reviewStates(): array
    {
        return [
            'Accepted' => ['approved', 'accepted'],
            'Refused1' => ['request_changes', 'refused'],
            'Refused2' => ['changes_requested', 'refused'],
            'Commented' => ['commented', 'commented'],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_take_into_account_comments_coming_from_author(): void
    {
        $authorUserId = 1;
        $authorsOwnComment = [
            'action' => 'submitted',
            'pull_request' => ['number' => self::PR_NUMBER, 'user' => ['id' => $authorUserId]],
            'review' => ['state' => 'commented', 'user' => ['id' => $authorUserId]],
        ];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->pullRequestReviewHandler->handle($authorsOwnComment);
    }

    /**
     * @test
     */
    public function it_does_not_take_into_account_edited_comments(): void
    {
        $commentEdited = ['action' => 'edited', 'review' => []];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->pullRequestReviewHandler->handle($commentEdited);
    }
}
