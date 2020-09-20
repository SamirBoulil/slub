<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PullRequestReviewEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@gmail.com>
 */
class PullRequestReviewEventHandlerTest extends TestCase
{
    private const PR_NUMBER = 10;
    private const REPOSITORY_IDENTIFIER = 'SamirBoulil/slub';
    private const PR_IDENTIFIER = 'SamirBoulil/slub/10';

    /**
     * @var PullRequestReviewEventHandler
     * @sut
     */
    private $pullRequestReviewHandler;

    /** @var ObjectProphecy|NewReviewHandler */
    private $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(NewReviewHandler::class);
        $this->pullRequestReviewHandler = new PullRequestReviewEventHandler($this->handler->reveal());
    }

    /**
     * @test
     */
    public function it_only_listens_to_pull_request_review_events()
    {
        self::assertTrue($this->pullRequestReviewHandler->supports('pull_request_review'));
        self::assertFalse($this->pullRequestReviewHandler->supports('unsupported_event'));
    }

    /**
     * @test
     * @dataProvider reviewStates
     */
    public function it_listens_to_accepted_PR(string $reviewState, string $expectedReviewStatus)
    {
        $newReview = [
            'action' => 'submitted',
            'pull_request' => ['number' => self::PR_NUMBER, 'user' => ['id' => 1, 'login' => 'lucie']],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
            'review' => ['state' => $reviewState, 'user' => ['id' => 2, 'login' => 'samir']],
        ];

        $this->handler->handle(
            Argument::that(
                function (NewReview $newReview) use ($expectedReviewStatus) {
                    return self::PR_IDENTIFIER === $newReview->PRIdentifier
                        && self::REPOSITORY_IDENTIFIER === $newReview->repositoryIdentifier
                        && $expectedReviewStatus === $newReview->reviewStatus;
                }
            )
        )->shouldBeCalled();

        $this->pullRequestReviewHandler->handle($newReview);
    }

    public function reviewStates()
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
    public function it_does_not_take_into_account_comments_coming_from_author()
    {
        $authorUserId = 1;
        $authorsOwnComment = [
            'action' => 'submitted',
            'pull_request' => ['user' => ['id' => $authorUserId]],
            'review' => ['user' => ['id' => $authorUserId]],
        ];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->pullRequestReviewHandler->handle($authorsOwnComment);
    }

    /**
     * @test
     */
    public function it_does_not_take_into_account_edited_comments()
    {
        $commentEdited = ['action' => 'edited', 'review' => []];

        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->pullRequestReviewHandler->handle($commentEdited);
    }

    /**
     * @test
     */
    public function it_throws_if_the_status_is_not_supported()
    {
        $unsupportedReviewStatus = [
            'action' => 'submitted',
            'pull_request' => ['number' => self::PR_NUMBER, 'user' => ['id' => 1, 'login' => 'lucie']],
            'repository' => ['full_name' => self::REPOSITORY_IDENTIFIER],
            'review' => ['state' => 'UNSUPPORTED_STATUS', 'user' => ['id' => 2, 'login' => 'samir']],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->pullRequestReviewHandler->handle($unsupportedReviewStatus);
    }
}
