<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\VCS\Github\EventHandler;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Slub\Infrastructure\VCS\Github\EventHandler\PullRequestReviewEventHandler;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
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
            'pull_request' => ['number' => self::PR_NUMBER],
            'repository'   => ['full_name' => self::REPOSITORY_IDENTIFIER],
            'review'       => ['state' => $reviewState]
        ];

        $this->handler->handle(
            Argument::that(
                function (NewReview $newReview) use ($expectedReviewStatus)
                {
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
            'Accepted'  => ['approved', 'accepted'],
            'Refused'   => ['request_changes', 'refused'],
            'Commented' => ['commented', 'commented']
        ];
    }

    /**
     * @test
     */
    public function it_throws_if_the_status_is_not_supported()
    {
        $newReview = [
            'pull_request' => ['number' => self::PR_NUMBER],
            'repository'   => ['full_name' => self::REPOSITORY_IDENTIFIER],
            'review'       => ['state' => 'UNSUPPORTED']
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->handler->handle(Argument::any())->shouldNotBeCalled();

        $this->pullRequestReviewHandler->handle($newReview);
    }
}
