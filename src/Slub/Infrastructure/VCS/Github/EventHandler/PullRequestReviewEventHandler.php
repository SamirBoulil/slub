<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Samir Boulil <samir.boulil@gmail.com>
 */
class PullRequestReviewEventHandler implements EventHandlerInterface
{
    private const NEW_REVIEW_EVENT_TYPE = 'pull_request_review';
    const EDITED_ACTION_TYPE = 'edited';

    /** @var NewReviewHandler */
    private $newReviewHandler;

    public function __construct(NewReviewHandler $newReviewHandler)
    {
        $this->newReviewHandler = $newReviewHandler;
    }

    public function supports(string $eventType): bool
    {
        return self::NEW_REVIEW_EVENT_TYPE === $eventType;
    }

    public function handle(array $PRStatusUpdate): void
    {
        if ($this->reviewIsAnEdit($PRStatusUpdate)
            || $this->authorReviewedHisOwnPR($PRStatusUpdate)
        ) {
            return;
        }

        $newPRReview = $this->createNewReview($PRStatusUpdate);
        $this->newReviewHandler->handle($newPRReview);
    }

    private function createNewReview(array $PRStatusUpdate): NewReview
    {
        $newPRReview = new NewReview();
        $newPRReview->PRIdentifier = $this->getPRIdentifier($PRStatusUpdate);
        $newPRReview->repositoryIdentifier = $this->getRepositoryIdentifier($PRStatusUpdate);
        $newPRReview->reviewStatus = $this->reviewStatus($PRStatusUpdate);
        $newPRReview->reviewerName = $this->reviewerName($PRStatusUpdate);

        return $newPRReview;
    }

    private function getPRIdentifier(array $PRStatusUpdate): string
    {
        return sprintf('%s/%s', $PRStatusUpdate['repository']['full_name'], $PRStatusUpdate['pull_request']['number']);
    }

    private function getRepositoryIdentifier(array $PRStatusUpdate): string
    {
        return $PRStatusUpdate['repository']['full_name'];
    }

    private function reviewStatus(array $PRStatusUpdate): string
    {
        $PRStatus = $PRStatusUpdate['review']['state'];

        switch ($PRStatus) {
            case 'approved':
                return 'accepted';
            case 'request_changes':
            case 'changes_requested':
                return 'refused';
            case 'commented':
                return 'commented';
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unknown review status "%s", expected one of "approved", "request_changes", "commented"',
                        $PRStatus
                    )
                );
        }
    }

    private function authorReviewedHisOwnPR($PRStatusUpdate): bool
    {
        return $PRStatusUpdate['review']['user']['id'] === $PRStatusUpdate['pull_request']['user']['id'];
    }

    private function reviewerName(array $PRStatusUpdate): string
    {
        return $PRStatusUpdate['review']['user']['login'];
    }

    private function reviewIsAnEdit(array $PRStatusUpdate): bool
    {
        return self::EDITED_ACTION_TYPE === $PRStatusUpdate['action'];
    }
}
