<?php

declare(strict_types=1);

namespace Slub\Infrastructure\VCS\Github\EventHandler;

use Slub\Application\NewReview\NewReview;
use Slub\Application\NewReview\NewReviewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Samir Boulil <samir.boulil@akeneo.com>
 */
class PullRequestReviewEventHandler implements EventHandlerInterface
{
    public const NEW_REVIEW_EVENT_TYPE = 'pull_request_review';

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

    public function handle(Request $request): void
    {
        $PRStatusUpdate = $this->getPRStatusUpdate($request);
        $this->updatePRStatus($PRStatusUpdate);
    }

    private function getPRStatusUpdate(Request $request): array
    {
        return json_decode((string) $request->getContent(), true);
    }

    private function updatePRStatus(array $PRStatusUpdate): void
    {
        $newPRReview = new NewReview();
        $newPRReview->PRIdentifier = $this->getPRIdentifier($PRStatusUpdate);
        $newPRReview->repositoryIdentifier = $this->getRepositoryIdentifier($PRStatusUpdate);
        $newPRReview->reviewStatus = $this->reviewStatus($PRStatusUpdate);
        $this->newReviewHandler->handle($newPRReview);
    }

    private function getPRIdentifier(array $PRStatusUpdate): string
    {
        $PRUrl = $PRStatusUpdate['review']['html_url'];
        preg_match('|https://github.com/(.*)/pull/(.*)#.*$|', $PRUrl, $matches);

        return $matches[1].'/'.$matches[2];
    }

    private function getRepositoryIdentifier(array $PRStatusUpdate): string
    {
        $PRUrl = $PRStatusUpdate['review']['html_url'];
        preg_match('#https://github.com/(.*)/pull/.*$#', $PRUrl, $matches);

        return $matches[1];
    }

    private function reviewStatus(array $PRStatusUpdate): string
    {
        $PRStatus = $PRStatusUpdate['review']['state'];

        switch ($PRStatus) {
            case 'approved':
                return 'accepted';
            case 'request_changes':
                return 'refused';
            case 'commented':
                return 'commented';
            default:
                throw new BadRequestHttpException(
                    sprintf(
                        'Unkown review status "%s", expected one of "approved", "request_changes", "commented"',
                        $PRStatus
                    )
                );
        }
    }
}
